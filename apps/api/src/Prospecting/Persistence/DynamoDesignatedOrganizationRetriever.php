<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\Candidate;
use Prospecter\Core\Prospecting\Application\Port\DesignatedOrganizationRetriever;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Company\Siret;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReference;

/**
 * Reads the account's organization that a key designates, from the reservations that `DynamoOrganizationRepository`
 * writes (`SIRET#`, `REFERENCE#`, `WEBSITE#`, `EMAIL#`). It does not restore the organization (ADR 0088).
 */
final readonly class DynamoDesignatedOrganizationRetriever implements DesignatedOrganizationRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function withSiret(AccountId $accountId, Siret $siret): ?OrganizationId
    {
        return $this->holderOf(OrganizationKeys::SIRET, $accountId, $siret->value);
    }

    public function withReference(AccountId $accountId, ExternalReference $reference): ?OrganizationId
    {
        return $this->holderOf(OrganizationKeys::REFERENCE, $accountId, $reference->key());
    }

    public function withWebsiteDomain(AccountId $accountId, string $domain): ?OrganizationId
    {
        return $this->holderOf(OrganizationKeys::WEBSITE, $accountId, $domain);
    }

    public function withEmail(AccountId $accountId, Email $email): ?OrganizationId
    {
        return $this->holderOf(OrganizationKeys::EMAIL, $accountId, $email->value);
    }

    /**
     * @param list<OrganizationId> $ids
     */
    public function named(array $ids): array
    {
        $items = $this->prospectingTable->itemsInTheOrderOf(array_map(static fn (OrganizationId $id): string => OrganizationKeys::organization($id->value), $ids), OrganizationKeys::ORGANIZATION);

        return array_map(static fn (array $item): Candidate => self::candidate(Typed::document($item['organization'])), $items);
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function candidate(array $document): Candidate
    {
        $name = Documents::legalIdentityOf($document['legalIdentity'])->fullName();

        return new Candidate(Typed::string($document['id']), '' === $name ? Typed::string(Typed::document($document['contactDetails'])['email']) : $name);
    }

    private function holderOf(string $kind, AccountId $accountId, string $value): ?OrganizationId
    {
        $holder = Typed::stringOrNull($this->prospectingTable->item(OrganizationKeys::reservation($kind, $accountId->value, $value), $kind)['ownerId'] ?? null);

        return null === $holder ? null : new OrganizationId($holder);
    }
}
