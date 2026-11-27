<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\OrganizationOfContact;
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

        $documents = $this->documentsOf(array_values(array_unique(array_merge([], ...array_values($linked)))));
        $ofContacts = [];

        foreach ($linked as $person => $keys) {
            $ofContacts[$person] = array_values(array_map(static fn (string $key): OrganizationOfContact => self::organizationOf($documents[$key], $person), array_filter($keys, static fn (string $key): bool => isset($documents[$key]))));
        }

        return $ofContacts;
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function organizationOf(array $document, string $person): OrganizationOfContact
    {
        return new OrganizationOfContact(Typed::string($document['id']), Documents::legalIdentityOf($document['legalIdentity'])->fullName(), OrganizationDocument::attachedAtOf($document)[$person] ?? null);
    }

    /**
     * @param list<string> $keys the organizations' partition keys
     *
     * @return array<string, array<string, mixed>> the organizations' documents, indexed by key
     */
    private function documentsOf(array $keys): array
    {
        $documents = [];

        foreach ($this->prospectingTable->items(array_map(static fn (string $key): array => [$key, OrganizationKeys::ORGANIZATION], $keys)) as $item) {
            $documents[Typed::string($item['PK'])] = Typed::document($item['organization']);
        }

        return $documents;
    }
}
