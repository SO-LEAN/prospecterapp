<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts\OrganizationOfContact;
use Prospecter\Core\Prospecting\Application\Port\OrganizationOfContactRetriever;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Reads the links `CONTACT#<person>` / `ORGANIZATION#<id>` that `DynamoOrganizationRepository` writes, then the
 * organizations they name, without restoring them (ADR 0088): one batch for all the contacts.
 */
final readonly class DynamoOrganizationOfContactRetriever implements OrganizationOfContactRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function ofContacts(array $contacts, AccountId $accountId): array
    {
        $linked = [];

        foreach ($contacts as $contact) {
            $links = array_filter($this->prospectingTable->query(OrganizationKeys::contact($contact->value)), static fn (array $link): bool => $link['accountId'] === $accountId->value);
            $linked[$contact->value] = array_values(array_map(static fn (array $link): string => $link['SK'], $links));
        }

        $organizations = $this->organizationsNamed(array_values(array_unique(array_merge([], ...array_values($linked)))));

        return array_map(static fn (array $keys): array => array_values(array_filter(array_map(static fn (string $key): ?OrganizationOfContact => $organizations[$key] ?? null, $keys))), $linked);
    }

    /**
     * @param list<string> $keys partition keys of organizations
     *
     * @return array<string, OrganizationOfContact> by key
     */
    private function organizationsNamed(array $keys): array
    {
        $named = [];

        foreach ($this->prospectingTable->items(array_map(static fn (string $key): array => [$key, OrganizationKeys::ORGANIZATION], $keys)) as $item) {
            $document = $item['organization'];
            $named[$item['PK']] = new OrganizationOfContact($document['id'], Documents::legalIdentityOf($document['legalIdentity'])->fullName());
        }

        return $named;
    }
}
