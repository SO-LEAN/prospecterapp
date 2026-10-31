<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\SearchIndexes;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The organizations in the table of Prospecting (ADR 0048):
 * - `ORGANIZATION#<id>` / `ORGANIZATION`: the organization, listed by account in `GSI1` (`ACCOUNT#<id>`, its id)
 *   and by holding in `GSI2` (`HOLDING#<id>`, its id);
 * - `EMAIL#<account>#<email>` / `EMAIL`: the email an organization holds in its account, the safety net of
 *   EmailUniqueness (ADR 0039, 0048): two organizations taking it at once, the second transaction is cancelled;
 * - `CONTACT#<person>` / `ORGANIZATION#<id>`: the organizations naming a contact.
 * The organization carries its search keys and its phone number among the organizations of its account
 * (`SearchIndexes`).
 */
final readonly class DynamoOrganizationRepository implements OrganizationRepository
{
    private const string EMAIL = 'EMAIL';

    public function __construct(private Table $prospectingTable)
    {
    }

    public function get(OrganizationId $id): Organization
    {
        $item = $this->prospectingTable->item(OrganizationKeys::organization($id->value), OrganizationKeys::ORGANIZATION) ?? throw UnknownOrganization::identifiedBy($id);

        return OrganizationDocument::organization($item['organization']);
    }

    /**
     * @param list<OrganizationId> $ids
     */
    public function ofIds(array $ids): array
    {
        return $this->organizationsOf($this->prospectingTable->items(array_map(static fn (OrganizationId $id): array => [OrganizationKeys::organization($id->value), OrganizationKeys::ORGANIZATION], $ids)));
    }

    public function isEmailUsed(AccountId $accountId, Email $email, OrganizationId $except): bool
    {
        $holder = $this->prospectingTable->item(self::emailKey($accountId, $email->value), self::EMAIL);

        return null !== $holder && $holder['ownerId'] !== $except->value;
    }

    public function save(Organization $organization): void
    {
        $document = OrganizationDocument::of($organization);
        $stored = $this->prospectingTable->item(OrganizationKeys::organization($organization->id()->value), OrganizationKeys::ORGANIZATION);
        $writes = [$this->prospectingTable->putting($this->itemOf($organization, $document))];
        $email = $document['contactDetails']['email'];
        $storedEmail = $stored['email'] ?? null;

        $reservation = null;

        if ($email !== $storedEmail && null !== $email) {
            $reservation = count($writes);
            $writes[] = $this->prospectingTable->putting(['PK' => self::emailKey($organization->accountId(), $email), 'SK' => self::EMAIL, 'ownerId' => $document['id']], 'attribute_not_exists(PK) OR ownerId = :owner', [':owner' => $document['id']]);
        }

        if ($email !== $storedEmail && null !== $storedEmail) {
            $writes[] = $this->prospectingTable->deleting(self::emailKey($organization->accountId(), $storedEmail), self::EMAIL);
        }

        try {
            $this->prospectingTable->transact([...$writes, ...$this->contactLinksChanged($document, $stored['organization']['contacts'] ?? [])]);
        } catch (ConditionFailed $taken) {
            throw null !== $reservation && $taken->failed($reservation) ? EmailAlreadyUsed::byAnother($taken) : $taken;
        }
    }

    public function remove(Organization $organization): void
    {
        $document = OrganizationDocument::of($organization);
        $email = $document['contactDetails']['email'];

        $this->prospectingTable->transact([
            $this->prospectingTable->deleting(OrganizationKeys::organization($organization->id()->value), OrganizationKeys::ORGANIZATION),
            ...(null === $email ? [] : [$this->prospectingTable->deleting(self::emailKey($organization->accountId(), $email), self::EMAIL)]),
            ...$this->contactLinksChanged(['contacts' => []] + $document, $document['contacts']),
        ]);
    }

    public function subsidiariesOf(OrganizationId $holding): array
    {
        return $this->organizationsOf($this->prospectingTable->query('HOLDING#'.$holding->value, Table::BY_GSI2));
    }

    public function withContacts(array $contacts, AccountId $accountId): array
    {
        $named = [];

        foreach ($contacts as $contact) {
            foreach ($this->prospectingTable->query(OrganizationKeys::contact($contact->value)) as $link) {
                if ($link['accountId'] === $accountId->value) {
                    $named[$link['organizationId']] = [OrganizationKeys::organization($link['organizationId']), OrganizationKeys::ORGANIZATION];
                }
            }
        }

        return $this->organizationsOf($this->prospectingTable->items(array_values($named)));
    }

    /**
     * Writes again the search keys of every organization, and forgets the items of the former search (ADR
     * 0050): what a table written before ADR 0071 needs once.
     */
    public function writeTheSearchKeysAgain(): int
    {
        $items = $this->prospectingTable->scan(OrganizationKeys::ORGANIZATION.'#');
        $this->prospectingTable->batch(
            array_map(fn (array $item): array => $this->itemOf(OrganizationDocument::organization($item['organization']), $item['organization']), $items),
            array_map(static fn (array $former): array => [$former['PK'], $former['SK']], $this->prospectingTable->scan('TOKEN')),
        );

        return count($items);
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    private function itemOf(Organization $organization, array $document): array
    {
        return SearchIndexes::of($organization->accountId()->value, $organization->searchKeys(), $organization->contactDetails()->phoneNumber) + [
            'PK' => OrganizationKeys::organization($document['id']),
            'SK' => OrganizationKeys::ORGANIZATION,
            'GSI1PK' => 'ACCOUNT#'.$document['accountId'],
            'GSI1SK' => $document['id'],
            'email' => $document['contactDetails']['email'],
            'organization' => $document,
        ] + (null === $document['holdingId'] ? [] : ['GSI2PK' => 'HOLDING#'.$document['holdingId'], 'GSI2SK' => $document['id']]);
    }

    /**
     * The links to write and to delete so that the contacts of the document are those named by the table.
     *
     * @param array<string, mixed> $document
     * @param list<string>         $storedContacts
     *
     * @return list<array<string, mixed>>
     */
    private function contactLinksChanged(array $document, array $storedContacts): array
    {
        $organization = OrganizationKeys::organization($document['id']);

        return [
            ...array_map(fn (string $contact): array => $this->prospectingTable->putting(['PK' => OrganizationKeys::contact($contact), 'SK' => $organization, 'accountId' => $document['accountId'], 'organizationId' => $document['id']]), array_values(array_diff($document['contacts'], $storedContacts))),
            ...array_map(fn (string $contact): array => $this->prospectingTable->deleting(OrganizationKeys::contact($contact), $organization), array_values(array_diff($storedContacts, $document['contacts']))),
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return list<Organization>
     */
    private function organizationsOf(array $items): array
    {
        return array_map(static fn (array $item): Organization => OrganizationDocument::organization($item['organization']), $items);
    }

    private static function emailKey(AccountId $accountId, string $email): string
    {
        return sprintf('%s#%s#%s', self::EMAIL, $accountId->value, $email);
    }
}
