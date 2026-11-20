<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\IdentifyOrganizations;

use Prospecter\Core\Prospecting\Application\Port\DesignatedOrganizationRetriever;
use Prospecter\Core\Prospecting\Application\Port\FoundOrganizationRetriever;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\FoundOrganization;
use Prospecter\Core\Prospecting\Domain\Model\CorporateNameKey;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationKeys;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\SearchKey;

final readonly class IdentifyOrganizationsHandler implements QueryHandler
{
    /**
     * The organizations whose name begins like the one given compared by name, enough for a name shared by several.
     */
    private const int NAMED_ALIKE_AT_MOST = 50;

    public function __construct(
        private DesignatedOrganizationRetriever $designatedOrganizationRetriever,
        private FoundOrganizationRetriever $foundOrganizationRetriever,
        private Prospectors $prospectors,
    ) {
    }

    public function __invoke(IdentifyOrganizations $query, IdentifyOrganizationsOutput $output): void
    {
        $accountId = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId))->accountId;

        $output->present(array_map(fn (OrganizationClues $clues): Identification => $this->identify($accountId, $clues), $query->clues));
    }

    private function identify(AccountId $accountId, OrganizationClues $clues): Identification
    {
        $keys = OrganizationKeys::among($clues->siret, $clues->links, $clues->website, $clues->emails);
        $candidates = $this->designatedOrganizationRetriever->named($this->designatedBy($accountId, $keys));

        return match (count($candidates)) {
            0 => $this->identifyByName($accountId, $clues),
            1 => Identification::known($candidates[0]),
            default => Identification::toConfirm($candidates),
        };
    }

    private function identifyByName(AccountId $accountId, OrganizationClues $clues): Identification
    {
        $nameKey = CorporateNameKey::of($clues->name);
        $namedAlike = null === $nameKey ? [] : $this->namedAlike($accountId, $nameKey);
        usort($namedAlike, static fn (FoundOrganization $one, FoundOrganization $other): int => self::isIn($other, $clues->city) <=> self::isIn($one, $clues->city));

        return [] === $namedAlike ? Identification::new() : Identification::toConfirm(array_map(self::candidate(...), $namedAlike));
    }

    /**
     * @return list<FoundOrganization>
     */
    private function namedAlike(AccountId $accountId, CorporateNameKey $nameKey): array
    {
        $found = $this->foundOrganizationRetriever->sliceProspectedBy($accountId, SearchKey::of($nameKey->value), null, self::NAMED_ALIKE_AT_MOST)->organizations;

        return array_values(array_filter($found, static fn (FoundOrganization $organization): bool => CorporateNameKey::of($organization->fullName)?->value === $nameKey->value));
    }

    private static function isIn(FoundOrganization $organization, ?string $city): bool
    {
        $place = SearchKey::of($city);

        return null !== $place && SearchKey::of($organization->city)?->value === $place->value;
    }

    private static function candidate(FoundOrganization $organization): Candidate
    {
        return new Candidate($organization->id, $organization->fullName);
    }

    /**
     * The organizations the keys designate, each once, by the surest key first.
     *
     * @return list<OrganizationId>
     */
    private function designatedBy(AccountId $accountId, OrganizationKeys $keys): array
    {
        $designated = array_map(static fn (OrganizationId $id): string => $id->value, $this->designatedByEachKey($accountId, $keys));

        return array_values(array_map(static fn (string $id): OrganizationId => new OrganizationId($id), array_unique($designated)));
    }

    /**
     * @return list<OrganizationId>
     */
    private function designatedByEachKey(AccountId $accountId, OrganizationKeys $keys): array
    {
        $retriever = $this->designatedOrganizationRetriever;

        return array_values(array_filter([
            null === $keys->siret ? null : $retriever->withSiret($accountId, $keys->siret),
            ...array_map(static fn (ExternalReference $reference): ?OrganizationId => $retriever->withReference($accountId, $reference), $keys->references),
            null === $keys->website ? null : $retriever->withWebsiteDomain($accountId, $keys->website->domain()),
            ...array_map(static fn (Email $email): ?OrganizationId => $retriever->withEmail($accountId, $email), $keys->emails),
            ...array_map(static fn (string $domain): ?OrganizationId => $retriever->withWebsiteDomain($accountId, $domain), $keys->emailDomains()),
        ]));
    }
}
