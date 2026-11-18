<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Revision;
use App\Shared\Persistence\SearchIndexes;
use App\Shared\Persistence\Table;
use App\Shared\Persistence\Write;
use Prospecter\Core\Prospecting\Domain\Exception\SiretAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Siret;

/**
 * The organizations in the table of Prospecting (ADR 0048):
 * - `ORGANIZATION#<id>` / `ORGANIZATION`: the organization, listed by account in `GSI1` (`ACCOUNT#<id>`, its id)
 *   and by holding in `GSI2` (`HOLDING#<id>`, its id);
 * - `EMAIL#<account>#<email>` / `EMAIL`: the email an organization holds in its account, the safety net of
 *   EmailUniqueness (ADR 0039, 0048): two organizations taking it at once, the second transaction is cancelled;
 * - `SIRET#<account>#<siret>` / `SIRET`: the establishment an organization is in its account, the safety net of
 *   SiretUniqueness, as for the email;
 * - `CONTACT#<person>` / `ORGANIZATION#<id>`: the organizations naming a contact;
 * - `EMAIL_DOMAIN#<account>#<domain>` / `ORGANIZATION#<id>`: the organizations of an account by the domain of their
 *   email, the one to attach a contact of that domain to proposed first (ADR 0146).
 * The organization carries its search keys and its phone number among the organizations of its account
 * (`SearchIndexes`), and its `revision`, incremented at each save: the revision of its facts, which the aggregate
 * does not know.
 */
final readonly class DynamoOrganizationRepository implements OrganizationRepository
{
    private const string EMAIL = 'EMAIL';
    private const string SIRET = 'SIRET';

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

    public function withSiret(AccountId $accountId, Siret $siret, OrganizationId $except): ?Organization
    {
        $holder = $this->prospectingTable->item(self::siretKey($accountId->value, $siret->value), self::SIRET);

        return null === $holder || $holder['ownerId'] === $except->value ? null : $this->get(new OrganizationId($holder['ownerId']));
    }

    public function save(Organization $organization): void
    {
        $document = OrganizationDocument::of($organization);
        $stored = $this->prospectingTable->item(OrganizationKeys::organization($organization->id()->value), OrganizationKeys::ORGANIZATION);
        $writes = [$this->prospectingTable->putting($this->itemOf($organization, $document, Revision::after($stored)))];
        $email = $document['contactDetails']['email'];
        $storedEmail = $stored['email'] ?? null;

        if ($email !== $storedEmail && null !== $email) {
            $writes[] = $this->prospectingTable->putting(['PK' => self::emailKey($organization->accountId(), $email), 'SK' => self::EMAIL, 'ownerId' => $document['id']], 'attribute_not_exists(PK) OR ownerId = :owner', [':owner' => $document['id']], EmailAlreadyUsed::byAnother(...));
        }

        if ($email !== $storedEmail && null !== $storedEmail) {
            $writes[] = $this->prospectingTable->deleting(self::emailKey($organization->accountId(), $storedEmail), self::EMAIL);
        }

        $this->prospectingTable->transact([...$writes, ...$this->siretReservationChanged($document, $stored['organization']['legalIdentity']['siret'] ?? null), ...$this->emailDomainLinkChanged($document['accountId'], $document['id'], $email, $storedEmail), ...$this->contactLinksChanged($document, $stored['organization']['contacts'] ?? [])]);
    }

    public function remove(Organization $organization): void
    {
        $document = OrganizationDocument::of($organization);
        $email = $document['contactDetails']['email'];

        $this->prospectingTable->transact([
            $this->prospectingTable->deleting(OrganizationKeys::organization($organization->id()->value), OrganizationKeys::ORGANIZATION),
            ...(null === $email ? [] : [
                $this->prospectingTable->deleting(self::emailKey($organization->accountId(), $email), self::EMAIL),
                $this->prospectingTable->deleting(...self::emailDomainKeys($document['accountId'], $email, $document['id'])),
            ]),
            ...$this->siretReservationChanged(['legalIdentity' => ['siret' => null]] + $document, $document['legalIdentity']['siret']),
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
     * Writes again the item of every organization, its search keys and its keys in its lists with it, and the link of
     * the domain of its email (ADR 0146), and forgets the items of the search before ADR 0071: what a table written
     * before them needs once.
     */
    public function writeTheKeysAgain(): int
    {
        $items = $this->prospectingTable->scan(OrganizationKeys::ORGANIZATION.'#');
        $withAnEmail = array_values(array_filter($items, static fn (array $item): bool => null !== $item['organization']['contactDetails']['email']));
        $this->prospectingTable->batch(
            [
                ...array_map(fn (array $item): array => $this->itemOf(OrganizationDocument::organization($item['organization']), $item['organization'], Revision::of($item)), $items),
                ...array_map(static fn (array $item): array => self::emailDomainLink($item['organization']['accountId'], $item['organization']['contactDetails']['email'], $item['organization']['id']), $withAnEmail),
            ],
            array_map(static fn (array $former): array => [$former['PK'], $former['SK']], $this->prospectingTable->scan('TOKEN')),
        );

        return count($items);
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    private function itemOf(Organization $organization, array $document, int $revision): array
    {
        return SearchIndexes::of($organization->accountId()->value, $organization->searchKeys(), $organization->contactDetails()->phoneNumber) + [
            'PK' => OrganizationKeys::organization($document['id']),
            'SK' => OrganizationKeys::ORGANIZATION,
            'GSI1PK' => OrganizationKeys::ofAccount($document['accountId']),
            'GSI1SK' => $document['id'],
            'email' => $document['contactDetails']['email'],
            Revision::ATTRIBUTE => $revision,
            'organization' => $document,
        ] + (null === $document['holdingId'] ? [] : ['GSI2PK' => 'HOLDING#'.$document['holdingId'], 'GSI2SK' => $document['id']]);
    }

    /**
     * The links to write and to delete so that the contacts of the document are those named by the table.
     *
     * @param array<string, mixed> $document
     * @param list<string>         $storedContacts
     *
     * @return list<Write>
     */
    private function contactLinksChanged(array $document, array $storedContacts): array
    {
        $organization = OrganizationKeys::organization($document['id']);

        return [
            ...array_map(fn (string $contact): Write => $this->prospectingTable->putting(['PK' => OrganizationKeys::contact($contact), 'SK' => $organization, 'accountId' => $document['accountId'], 'organizationId' => $document['id']]), array_values(array_diff($document['contacts'], $storedContacts))),
            ...array_map(fn (string $contact): Write => $this->prospectingTable->deleting(OrganizationKeys::contact($contact), $organization), array_values(array_diff($storedContacts, $document['contacts']))),
        ];
    }

    /**
     * The reservation of its SIRET taken when it changes, the former one released.
     *
     * @param array<string, mixed> $document
     *
     * @return list<Write>
     */
    private function siretReservationChanged(array $document, ?string $storedSiret): array
    {
        $siret = $document['legalIdentity']['siret'];

        if ($siret === $storedSiret) {
            return [];
        }

        return [
            ...(null === $siret ? [] : [$this->prospectingTable->putting(['PK' => self::siretKey($document['accountId'], $siret), 'SK' => self::SIRET, 'ownerId' => $document['id']], 'attribute_not_exists(PK) OR ownerId = :owner', [':owner' => $document['id']], SiretAlreadyUsed::byAnother(...))]),
            ...(null === $storedSiret ? [] : [$this->prospectingTable->deleting(self::siretKey($document['accountId'], $storedSiret), self::SIRET)]),
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

    /**
     * The link of the domain of its email written when the domain changes, the former one deleted: an email changed
     * within its domain keeps it.
     *
     * @return list<Write>
     */
    private function emailDomainLinkChanged(string $accountId, string $organizationId, ?string $email, ?string $storedEmail): array
    {
        $domain = null === $email ? null : new Email($email)->domain();
        $storedDomain = null === $storedEmail ? null : new Email($storedEmail)->domain();

        if ($domain === $storedDomain) {
            return [];
        }

        return [
            ...(null === $domain ? [] : [$this->prospectingTable->putting(self::emailDomainLink($accountId, (string) $email, $organizationId))]),
            ...(null === $storedDomain ? [] : [$this->prospectingTable->deleting(...self::emailDomainKeys($accountId, (string) $storedEmail, $organizationId))]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function emailDomainLink(string $accountId, string $email, string $organizationId): array
    {
        [$pk, $sk] = self::emailDomainKeys($accountId, $email, $organizationId);

        return ['PK' => $pk, 'SK' => $sk];
    }

    /**
     * @return array{string, string}
     */
    private static function emailDomainKeys(string $accountId, string $email, string $organizationId): array
    {
        return [OrganizationKeys::ofEmailDomain($accountId, new Email($email)->domain()), OrganizationKeys::organization($organizationId)];
    }

    private static function siretKey(string $accountId, string $siret): string
    {
        return sprintf('%s#%s#%s', self::SIRET, $accountId, $siret);
    }

    private static function emailKey(AccountId $accountId, string $email): string
    {
        return sprintf('%s#%s#%s', self::EMAIL, $accountId->value, $email);
    }
}
