<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\ConsultOrganizationsOfContacts\OrganizationOfContact;
use Prospecter\Core\Prospecting\Application\Port\OrganizationOfContactRetriever;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Reads the links `CONTACT#<person>` / `ORGANIZATION#<id>` that `DynamoOrganizationRepository` writes, then the
 * organizations they name. It does not restore them (ADR 0088). One batch reads them for all the contacts.
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
            $linked[$contact->value] = Typed::strings(array_column($links, 'SK'));
        }

        $organizations = $this->organizationsNamed(array_values(array_unique(array_merge([], ...array_values($linked)))));

        return array_map(static fn (array $keys): array => array_values(array_filter(array_map(static fn (string $key): ?OrganizationOfContact => $organizations[$key] ?? null, $keys))), $linked);
    }

    /**
     * @param list<string> $keys the organizations' partition keys
     *
     * @return array<string, OrganizationOfContact> indexed by key
     */
    private function organizationsNamed(array $keys): array
    {
        $named = [];

        foreach ($this->prospectingTable->items(array_map(static fn (string $key): array => [$key, OrganizationKeys::ORGANIZATION], $keys)) as $item) {
            $document = Typed::document($item['organization']);
            $named[Typed::string($item['PK'])] = new OrganizationOfContact(Typed::string($document['id']), Documents::legalIdentityOf($document['legalIdentity'])->fullName());
        }

        return $named;
    }
}
