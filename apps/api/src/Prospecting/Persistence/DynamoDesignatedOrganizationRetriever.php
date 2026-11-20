<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\Candidate;
use Prospecter\Core\Prospecting\Application\Port\DesignatedOrganizationRetriever;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Siret;

/**
 * The organization of an account a key designates, read in the reservations `DynamoOrganizationRepository` writes
 * (`SIRET#`, `REFERENCE#`, `WEBSITE#`, `EMAIL#`), without restoring it (ADR 0088).
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
        $items = $this->prospectingTable->items(array_map(static fn (OrganizationId $id): array => [OrganizationKeys::organization($id->value), OrganizationKeys::ORGANIZATION], $ids));
        $documents = array_column(array_map(static fn (array $item): array => $item['organization'], $items), null, 'id');
        $named = array_filter(array_map(static fn (OrganizationId $id): ?array => $documents[$id->value] ?? null, $ids));

        return array_values(array_map(self::candidate(...), $named));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function candidate(array $document): Candidate
    {
        $name = Documents::legalIdentityOf($document['legalIdentity'])->fullName();

        return new Candidate($document['id'], '' === $name ? $document['contactDetails']['email'] : $name);
    }

    private function holderOf(string $kind, AccountId $accountId, string $value): ?OrganizationId
    {
        $holder = $this->prospectingTable->item(OrganizationKeys::reservation($kind, $accountId->value, $value), $kind)['ownerId'] ?? null;

        return null === $holder ? null : new OrganizationId($holder);
    }
}
