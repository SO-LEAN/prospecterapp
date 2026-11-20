<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\Revision;
use App\Shared\Persistence\SearchIndexes;
use App\Shared\Persistence\Table;
use App\Shared\Persistence\Write;
use Prospecter\Core\Prospecting\Domain\Exception\ReferenceAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Exception\SiretAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Exception\WebsiteAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\ResemblanceKey;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
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
 * - `REFERENCE#<account>#<source>#<identifier>` / `REFERENCE` and `WEBSITE#<account>#<domain>` / `WEBSITE`: each
 *   link and the domain of the website an organization has in its account, the safety nets of ReferenceUniqueness
 *   and WebsiteUniqueness, as for the SIRET;
 * - `CONTACT#<person>` / `ORGANIZATION#<id>`: the organizations naming a contact;
 * - `EMAIL_DOMAIN#<account>#<domain>` / `ORGANIZATION#<id>`: the organizations of an account by the domain of their
 *   email, the one to attach a contact of that domain to proposed first (ADR 0146).
 * The organization carries its search keys and its phone number among the organizations of its account
 * (`SearchIndexes`), and its `revision`, incremented at each save: the revision of its facts, which the aggregate
 * does not know.
 */
final readonly class DynamoOrganizationRepository implements OrganizationRepository
{
    private const string EMAIL = OrganizationKeys::EMAIL;
    private const string SIRET = OrganizationKeys::SIRET;
    private const string REFERENCE = OrganizationKeys::REFERENCE;
    private const string FORMER_LINKEDIN_PAGE = 'LINKEDIN_PAGE';
    private const string WEBSITE = OrganizationKeys::WEBSITE;
    private const string RESEMBLANCE_KEY = 'resemblanceKey';

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

    /**
     * Found by the reservations of their emails in the account, without reading the organizations.
     *
     * @param list<Email> $emails
     */
    public function havingEmails(AccountId $accountId, array $emails): array
    {
        $emailsByKey = array_combine(array_map(static fn (Email $email): string => self::emailKey($accountId, $email->value), $emails), array_map(static fn (Email $email): string => $email->value, $emails));
        $reservations = $this->prospectingTable->items(array_map(static fn (string $key): array => [$key, self::EMAIL], array_keys($emailsByKey)));
        $organizations = [];
        foreach ($reservations as $reservation) {
            $organizations[$emailsByKey[$reservation['PK']]] = new OrganizationId($reservation['ownerId']);
        }

        return $organizations;
    }

    public function isEmailUsed(AccountId $accountId, Email $email, OrganizationId $except): bool
    {
        $holder = $this->prospectingTable->item(self::emailKey($accountId, $email->value), self::EMAIL);

        return null !== $holder && $holder['ownerId'] !== $except->value;
    }

    public function withSiret(AccountId $accountId, Siret $siret, OrganizationId $except): ?Organization
    {
        return $this->holderOf(self::SIRET, $accountId->value, $siret->value, $except);
    }

    public function withReference(AccountId $accountId, ExternalReference $reference, OrganizationId $except): ?Organization
    {
        return $this->holderOf(self::REFERENCE, $accountId->value, $reference->key(), $except);
    }

    public function withWebsiteOfDomain(AccountId $accountId, Website $website, OrganizationId $except): ?Organization
    {
        return $this->holderOf(self::WEBSITE, $accountId->value, $website->domain(), $except);
    }

    public function resembling(AccountId $accountId, ResemblanceKey $key, OrganizationId $except): array
    {
        $links = $this->prospectingTable->query(OrganizationKeys::ofResemblance($accountId->value, $key->value));
        $resembling = array_map(static fn (array $link): OrganizationId => new OrganizationId($link['organizationId']), $links);

        return array_values(array_filter($resembling, static fn (OrganizationId $id): bool => !$id->equals($except)));
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

        $this->prospectingTable->transact([...$writes, ...$this->reservationsChanged($document, $stored['organization'] ?? null), ...$this->emailDomainLinkChanged($document['accountId'], $document['id'], $email, $storedEmail), ...$this->resemblanceLinkChanged($document, $organization->resemblanceKey()?->value, $stored[self::RESEMBLANCE_KEY] ?? null), ...$this->contactLinksChanged($document, $stored['organization']['contacts'] ?? [])]);
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
            ...$this->reservationsChanged(null, $document),
            ...$this->resemblanceLinkChanged($document, null, $organization->resemblanceKey()?->value),
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
     * Writes again the item of every organization, its search keys and its keys in its lists with it, the link of
     * the domain of its email (ADR 0146), the link of its resemblance key and the reservations of its links and of the domain of its website, and forgets
     * the items of the search before ADR 0071 and the reservations of the LinkedIn pages before the links: what a table
     * written before them needs once.
     */
    public function writeTheKeysAgain(): int
    {
        $items = array_map(static fn (array $item): array => ['organization' => OrganizationDocument::recognizedAgain($item['organization'])] + $item, $this->prospectingTable->scan(OrganizationKeys::ORGANIZATION.'#'));
        $reservations = array_merge(...array_map(self::reservationsOf(...), array_column($items, 'organization')));
        $withAnEmail = array_values(array_filter($items, static fn (array $item): bool => null !== $item['organization']['contactDetails']['email']));
        $organizations = array_map(static fn (array $item): Organization => OrganizationDocument::organization($item['organization']), $items);
        $resembling = array_values(array_filter($organizations, static fn (Organization $organization): bool => null !== $organization->resemblanceKey()));
        $this->prospectingTable->batch(
            [
                ...array_map(fn (Organization $organization, array $item): array => $this->itemOf($organization, $item['organization'], Revision::of($item)), $organizations, $items),
                ...array_map(static fn (array $item): array => self::emailDomainLink($item['organization']['accountId'], $item['organization']['contactDetails']['email'], $item['organization']['id']), $withAnEmail),
                ...array_map(static fn (Organization $organization): array => self::resemblanceLink($organization->accountId()->value, (string) $organization->resemblanceKey(), $organization->id()->value), $resembling),
                ...$reservations,
            ],
            array_map(static fn (array $former): array => [$former['PK'], $former['SK']], [...$this->prospectingTable->scan('TOKEN'), ...$this->prospectingTable->scan(self::FORMER_LINKEDIN_PAGE.'#'), ...$this->referencesNoLongerHeld($reservations)]),
        );

        return count($items);
    }

    /**
     * The reservations of links no organization holds any more, once recognized again under another reference.
     *
     * @param list<array<string, string>> $reservations
     *
     * @return list<array<string, mixed>>
     */
    private function referencesNoLongerHeld(array $reservations): array
    {
        $held = array_column($reservations, 'PK');

        return array_values(array_filter($this->prospectingTable->scan(self::REFERENCE.'#'), static fn (array $reservation): bool => !in_array($reservation['PK'], $held, true)));
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
            self::RESEMBLANCE_KEY => $organization->resemblanceKey()?->value,
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
     * The reservations of what designates one organization of its account, its SIRET, its links and the domain of its
     * website, between the document stored and the one to store: none for an organization no longer prospected.
     *
     * @param array<string, mixed>|null $document
     * @param array<string, mixed>|null $stored
     *
     * @return list<Write>
     */
    private function reservationsChanged(?array $document, ?array $stored): array
    {
        $owner = $document ?? $stored;

        return [
            ...$this->reservationChanged(self::SIRET, $owner, $document['legalIdentity']['siret'] ?? null, $stored['legalIdentity']['siret'] ?? null, SiretAlreadyUsed::byAnother(...)),
            ...$this->referencesChanged($owner, null === $document ? [] : self::referenceKeysOf($document), null === $stored ? [] : self::referenceKeysOf($stored)),
            ...$this->reservationChanged(self::WEBSITE, $owner, self::domainOf($document['website'] ?? null), self::domainOf($stored['website'] ?? null), WebsiteAlreadyUsed::byAnother(...)),
        ];
    }

    /**
     * The reservation of a value only one organization of the account may have, taken when it changes, the former one
     * released.
     *
     * @param array<string, mixed>                         $owner
     * @param \Closure(ConditionFailed): BusinessException $takenMeanwhile
     *
     * @return list<Write>
     */
    private function reservationChanged(string $kind, array $owner, ?string $value, ?string $stored, \Closure $takenMeanwhile): array
    {
        if ($value === $stored) {
            return [];
        }

        return [
            ...(null === $value ? [] : [$this->prospectingTable->putting(['PK' => self::reservationKey($kind, $owner['accountId'], $value), 'SK' => $kind, 'ownerId' => $owner['id']], 'attribute_not_exists(PK) OR ownerId = :owner', [':owner' => $owner['id']], $takenMeanwhile)]),
            ...(null === $stored ? [] : [$this->prospectingTable->deleting(self::reservationKey($kind, $owner['accountId'], $stored), $kind)]),
        ];
    }

    /**
     * The reservations of the links taken, those of the links dropped released.
     *
     * @param array<string, mixed> $owner
     * @param list<string>         $keys
     * @param list<string>         $storedKeys
     *
     * @return list<Write>
     */
    private function referencesChanged(array $owner, array $keys, array $storedKeys): array
    {
        return [
            ...array_map(fn (string $key): Write => $this->prospectingTable->putting(['PK' => self::reservationKey(self::REFERENCE, $owner['accountId'], $key), 'SK' => self::REFERENCE, 'ownerId' => $owner['id']], 'attribute_not_exists(PK) OR ownerId = :owner', [':owner' => $owner['id']], ReferenceAlreadyUsed::byAnother(...)), array_values(array_diff($keys, $storedKeys))),
            ...array_map(fn (string $key): Write => $this->prospectingTable->deleting(self::reservationKey(self::REFERENCE, $owner['accountId'], $key), self::REFERENCE), array_values(array_diff($storedKeys, $keys))),
        ];
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return list<string>
     */
    private static function referenceKeysOf(array $document): array
    {
        return array_map(static fn (ExternalReference $reference): string => $reference->key(), OrganizationDocument::referencesOf($document)->all());
    }

    private function holderOf(string $kind, string $accountId, string $value, OrganizationId $except): ?Organization
    {
        $holder = $this->prospectingTable->item(self::reservationKey($kind, $accountId, $value), $kind);

        return null === $holder || $holder['ownerId'] === $except->value ? null : $this->get(new OrganizationId($holder['ownerId']));
    }

    /**
     * The reservations of what designates the organization of the document in its account.
     *
     * @param array<string, mixed> $document
     *
     * @return list<array<string, string>>
     */
    private static function reservationsOf(array $document): array
    {
        $reserved = [
            ...array_map(static fn (string $key): array => [self::REFERENCE, $key], self::referenceKeysOf($document)),
            ...array_map(static fn (string $domain): array => [self::WEBSITE, $domain], array_filter([self::domainOf($document['website'] ?? null)])),
        ];

        return array_map(static fn (array $reservation): array => ['PK' => self::reservationKey($reservation[0], $document['accountId'], $reservation[1]), 'SK' => $reservation[0], 'ownerId' => $document['id']], $reserved);
    }

    private static function domainOf(?string $website): ?string
    {
        return null === $website ? null : new Website($website)->domain();
    }

    private static function reservationKey(string $kind, string $accountId, string $value): string
    {
        return OrganizationKeys::reservation($kind, $accountId, $value);
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
     * The link of its resemblance key written when the key changes, the former one deleted.
     *
     * @param array<string, mixed> $document
     *
     * @return list<Write>
     */
    private function resemblanceLinkChanged(array $document, ?string $key, ?string $storedKey): array
    {
        if ($key === $storedKey) {
            return [];
        }

        return [
            ...(null === $key ? [] : [$this->prospectingTable->putting(self::resemblanceLink($document['accountId'], $key, $document['id']))]),
            ...(null === $storedKey ? [] : [$this->prospectingTable->deleting(OrganizationKeys::ofResemblance($document['accountId'], $storedKey), OrganizationKeys::organization($document['id']))]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function resemblanceLink(string $accountId, string $key, string $organizationId): array
    {
        return ['PK' => OrganizationKeys::ofResemblance($accountId, $key), 'SK' => OrganizationKeys::organization($organizationId), 'organizationId' => $organizationId];
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

    private static function emailKey(AccountId $accountId, string $email): string
    {
        return OrganizationKeys::reservation(self::EMAIL, $accountId->value, $email);
    }
}
