<?php

declare(strict_types=1);

namespace App\Consistency;

use App\PersonalData\Persistence\PersonDocument;
use App\Shared\Persistence\Documents;
use App\Shared\Typed;

/**
 * Compares the facts that the contacts' tags derive from, which Campaigns keeps, with the Prospecting and PersonalData
 * tables (ADR 0141). Campaigns may lack or keep stale an organization's contacts or locality (ADR 0142), or a contact's
 * reach. A rebuild of its account's tags corrects that. A fact kept for something gone, or a missed terminal fact, is
 * corrected by giving the reaction again.
 */
final readonly class TagsAgainstTheTables
{
    public function __construct(private ContextTables $tables)
    {
    }

    /**
     * @param list<string> $accountIds
     *
     * @return list<Discrepancy>
     */
    public function discrepanciesAmong(array $accountIds): array
    {
        return array_merge([], ...array_map($this->discrepanciesOf(...), $accountIds));
    }

    /**
     * @return list<Discrepancy>
     */
    private function discrepanciesOf(string $accountId): array
    {
        $organizationDocuments = $this->tables->organizationsOf($accountId);
        $organizationIds = Typed::strings(array_column($organizationDocuments, 'id'));
        $organizations = array_combine($organizationIds, array_map(static fn (array $organization): array => Typed::strings($organization['contacts']), $organizationDocuments));
        $localities = array_combine($organizationIds, array_map(self::localityOf(...), $organizationDocuments));
        $persons = $this->tables->contactDocumentsOf($accountId);
        $contacts = array_combine(Typed::strings(array_column($persons, 'id')), array_map(self::reachOf(...), $persons));
        $organizationFacts = $this->tables->organizationFactsOf($accountId);
        $contactFacts = $this->tables->contactFactsOf($accountId);

        return [
            ...self::outOfDate($accountId, $organizations, $organizationFacts, self::sorted(...), 'contacts of the organization out of date in Campaigns'),
            ...self::outOfDate($accountId, $localities, $this->tables->organizationLocalitiesOf($accountId), static fn (array $locality): array => $locality, 'locality of the organization out of date in Campaigns'),
            ...self::keptForWhatIsGone($accountId, $organizations, $organizationFacts, 'facts kept in Campaigns for an organization no longer prospected'),
            ...self::outOfDate($accountId, $contacts, $contactFacts, static fn (array $reach): array => $reach, 'reach of the contact out of date in Campaigns'),
            ...self::keptForWhatIsGone($accountId, $contacts, $contactFacts, 'facts kept in Campaigns for a forgotten contact'),
        ];
    }

    /**
     * @template T of array
     *
     * @param array<string, T>          $state    what the context's table keeps, by id
     * @param array<string, T>          $facts    what Campaigns keeps, by id
     * @param \Closure(T): array<mixed> $compared
     *
     * @return list<Discrepancy>
     */
    private static function outOfDate(string $accountId, array $state, array $facts, \Closure $compared, string $description): array
    {
        $outOfDate = array_filter(array_keys($state), static fn (string|int $id): bool => !isset($facts[$id]) || $compared($facts[$id]) !== $compared($state[$id]));

        return array_values(array_map(static fn (string|int $id): Discrepancy => new Discrepancy($accountId, (string) $id, $description, Correction::RebuildTheTags), $outOfDate));
    }

    /**
     * @param array<string, array<mixed>> $state
     * @param array<string, array<mixed>> $facts
     *
     * @return list<Discrepancy>
     */
    private static function keptForWhatIsGone(string $accountId, array $state, array $facts, string $description): array
    {
        return array_map(static fn (string|int $id): Discrepancy => new Discrepancy($accountId, (string) $id, $description, Correction::GiveTheReactionAgain), array_keys(array_diff_key($facts, $state)));
    }

    /**
     * @param array<string, mixed> $organization
     *
     * @return array{municipalityCode: ?string, country: ?string}
     */
    private static function localityOf(array $organization): array
    {
        $address = $organization['address'] ?? null;

        return ['municipalityCode' => Typed::stringOrNull($organization['municipalityCode'] ?? null), 'country' => null === $address ? null : Typed::stringOrNull(Typed::document($address)['country'] ?? null)];
    }

    /**
     * @param array<string, mixed> $person
     *
     * @return array{hasEmail: bool, hasPhone: bool, hasMobile: bool, hasLinkedIn: bool}
     */
    private static function reachOf(array $person): array
    {
        $contactDetails = Documents::contactDetailsOf(Typed::document($person['contactDetails']));

        return [
            'hasEmail' => null !== $contactDetails->email,
            'hasPhone' => null !== $contactDetails->phoneNumber,
            'hasMobile' => true === $contactDetails->phoneNumber?->isMobile(),
            'hasLinkedIn' => PersonDocument::isOnLinkedIn($person),
        ];
    }

    /**
     * @param list<string> $contacts
     *
     * @return list<string>
     */
    private static function sorted(array $contacts): array
    {
        sort($contacts);

        return $contacts;
    }
}
