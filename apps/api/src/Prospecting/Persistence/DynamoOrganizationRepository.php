<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Revision;
use App\Shared\Persistence\SearchIndexes;
use App\Shared\Persistence\Table;
use App\Shared\Persistence\Write;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Domain\Exception\ReferenceAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Exception\SiretAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Exception\WebsiteAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\ResemblanceKey;
use Prospecter\Core\Prospecting\Domain\Model\Website;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\Company\Siret;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Contact\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReference;

/**
 * Stores the organizations in Prospecting's table (ADR 0048):
 * - `ORGANIZATION#<id>` / `ORGANIZATION`: the organization. `GSI1` lists it by account (`ACCOUNT#<id>`, by its id),
 *   and `GSI2` lists it by holding (`HOLDING#<id>`, by its id).
 * - `EMAIL#<account>#<email>` / `EMAIL`: the email an organization holds in its account. It is EmailUniqueness's
 *   safety net (ADR 0039, 0048): when two organizations take the email at once, the second transaction is cancelled.
 * - `SIRET#<account>#<siret>` / `SIRET`: the establishment an organization is in its account. It is SiretUniqueness's
 *   safety net, like the email's.
 * - `REFERENCE#<account>#<source>#<identifier>` / `REFERENCE` and `WEBSITE#<account>#<domain>` / `WEBSITE`: each link
 *   and the website's domain that an organization has in its account. They are the safety nets of ReferenceUniqueness
 *   and WebsiteUniqueness, like the SIRET's.
 * - `CONTACT#<person>` / `ORGANIZATION#<id>`: the organizations that name a contact.
 * - `EMAIL_DOMAIN#<account>#<domain>` / `ORGANIZATION#<id>`: an account's organizations by their email's domain. The
 *   first one is proposed to attach a contact with an email in that domain (ADR 0146).
 * The organization carries its search keys and its phone number among its account's organizations (`SearchIndexes`).
 * It also carries its `revision`, which each save increments. It is the revision of its facts, which the aggregate
 * does not know. Its save and its removal also require the revision that their unit of work read (ADR 0181).
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
     * Finds the organizations through their emails' reservations in the account, without reading the organizations.
     *
     * @param list<Email> $emails
     */
    public function havingEmails(AccountId $accountId, array $emails): array
    {
        $emailsByKey = array_combine(array_map(static fn (Email $email): string => self::emailKey($accountId, $email->value), $emails), array_map(static fn (Email $email): string => $email->value, $emails));
        $reservations = $this->prospectingTable->items(array_map(static fn (string $key): array => [$key, self::EMAIL], array_keys($emailsByKey)));
        $organizations = [];
        foreach ($reservations as $reservation) {
            $organizations[$emailsByKey[Typed::string($reservation['PK'])]] = new OrganizationId(Typed::string($reservation['ownerId']));
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
        $resembling = array_map(static fn (array $link): OrganizationId => new OrganizationId(Typed::string($link['organizationId'])), $links);

        return array_values(array_filter($resembling, static fn (OrganizationId $id): bool => !$id->equals($except)));
    }

    public function save(Organization $organization): void
    {
        $document = OrganizationDocument::of($organization);
        $stored = $this->prospectingTable->item(OrganizationKeys::organization($organization->id()->value), OrganizationKeys::ORGANIZATION);
        $writes = [$this->prospectingTable->revising($this->itemOf($organization, $document, Revision::after($stored)))];
        $id = self::idOf($document);
        $email = self::emailOf($document);
        $storedEmail = Typed::stringOrNull($stored['email'] ?? null);
        $storedDocument = null === $stored ? null : Typed::document($stored['organization']);

        if ($email !== $storedEmail && null !== $email) {
            $writes[] = $this->prospectingTable->putting(['PK' => self::emailKey($organization->accountId(), $email), 'SK' => self::EMAIL, 'ownerId' => $id], 'attribute_not_exists(PK) OR ownerId = :owner', [':owner' => $id], EmailAlreadyUsed::byAnother(...));
        }

        if ($email !== $storedEmail && null !== $storedEmail) {
            $writes[] = $this->prospectingTable->deleting(self::emailKey($organization->accountId(), $storedEmail), self::EMAIL);
        }

        $this->prospectingTable->transact([...$writes, ...$this->reservationsChanged($document, $storedDocument), ...$this->emailDomainLinkChanged(self::accountOf($document), $id, $email, $storedEmail), ...$this->resemblanceLinkChanged($document, $organization->resemblanceKey()?->value, Typed::stringOrNull($stored[self::RESEMBLANCE_KEY] ?? null)), ...$this->contactLinksChanged($document, null === $storedDocument ? [] : self::contactsOf($storedDocument))]);
    }

    public function remove(Organization $organization): void
    {
        $document = OrganizationDocument::of($organization);
        $email = self::emailOf($document);

        $this->prospectingTable->transact([
            $this->prospectingTable->deletingRevised(OrganizationKeys::organization($organization->id()->value), OrganizationKeys::ORGANIZATION),
            ...(null === $email ? [] : [
                $this->prospectingTable->deleting(self::emailKey($organization->accountId(), $email), self::EMAIL),
                $this->prospectingTable->deleting(...self::emailDomainKeys(self::accountOf($document), $email, self::idOf($document))),
            ]),
            ...$this->reservationsChanged(null, $document),
            ...$this->resemblanceLinkChanged($document, null, $organization->resemblanceKey()?->value),
            ...$this->contactLinksChanged(['contacts' => []] + $document, self::contactsOf($document)),
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
                    $organizationId = Typed::string($link['organizationId']);
                    $named[$organizationId] = [OrganizationKeys::organization($organizationId), OrganizationKeys::ORGANIZATION];
                }
            }
        }

        return $this->organizationsOf($this->prospectingTable->items(array_values($named)));
    }

    /**
     * Writes again every organization's item, with its search keys and its keys in its lists. It also writes its email
     * domain's link (ADR 0146), its resemblance key's link, and the reservations of its links and of its website's
     * domain. It forgets the search items from before ADR 0071, and the LinkedIn pages' reservations from before the
     * links. A table written before these keys needs this once.
     */
    public function writeTheKeysAgain(): int
    {
        $items = array_map(static fn (array $item): array => ['organization' => OrganizationDocument::recognizedAgain(Typed::document($item['organization']))] + $item, $this->prospectingTable->scan(OrganizationKeys::ORGANIZATION.'#'));
        $documents = array_column($items, 'organization');
        $reservations = array_merge(...array_map(self::reservationsOf(...), $documents));
        $withAnEmail = array_values(array_filter($documents, static fn (array $document): bool => null !== self::emailOf($document)));
        $organizations = array_map(OrganizationDocument::organization(...), $documents);
        $resembling = array_values(array_filter($organizations, static fn (Organization $organization): bool => null !== $organization->resemblanceKey()));
        $this->prospectingTable->batch(
            [
                ...array_map(fn (Organization $organization, array $item): array => $this->itemOf($organization, $item['organization'], Revision::of($item)), $organizations, $items),
                ...array_map(static fn (array $document): array => self::emailDomainLink(self::accountOf($document), (string) self::emailOf($document), self::idOf($document)), $withAnEmail),
                ...array_map(static fn (Organization $organization): array => self::resemblanceLink($organization->accountId()->value, (string) $organization->resemblanceKey(), $organization->id()->value), $resembling),
                ...$reservations,
            ],
            array_map(static fn (array $former): array => [Typed::string($former['PK']), Typed::string($former['SK'])], [...$this->prospectingTable->scan('TOKEN'), ...$this->prospectingTable->scan(self::FORMER_LINKEDIN_PAGE.'#'), ...$this->referencesNoLongerHeld($reservations)]),
        );

        return count($items);
    }

    /**
     * The reservations of links that no organization holds any more, once the links are recognized again under another
     * reference.
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
     * @return array{PK: string, SK: string, ...}
     */
    private function itemOf(Organization $organization, array $document, int $revision): array
    {
        $id = self::idOf($document);
        $holdingId = Typed::stringOrNull($document['holdingId']);

        return [
            'PK' => OrganizationKeys::organization($id),
            'SK' => OrganizationKeys::ORGANIZATION,
            'GSI1PK' => OrganizationKeys::ofAccount(self::accountOf($document)),
            'GSI1SK' => $id,
            'email' => self::emailOf($document),
            self::RESEMBLANCE_KEY => $organization->resemblanceKey()?->value,
            Revision::ATTRIBUTE => $revision,
            'organization' => $document,
            ...SearchIndexes::of($organization->accountId()->value, $organization->searchKeys(), $organization->contactDetails()->phoneNumber),
            ...(null === $holdingId ? [] : ['GSI2PK' => 'HOLDING#'.$holdingId, 'GSI2SK' => $id]),
        ];
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function idOf(array $document): string
    {
        return Typed::string($document['id']);
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function accountOf(array $document): string
    {
        return Typed::string($document['accountId']);
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function emailOf(array $document): ?string
    {
        return Typed::stringOrNull(Typed::document($document['contactDetails'])['email'] ?? null);
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return list<string>
     */
    private static function contactsOf(array $document): array
    {
        return Typed::strings($document['contacts'] ?? []);
    }

    /**
     * Returns the links to write and to delete, so that the table names the document's contacts.
     *
     * @param array<string, mixed> $document
     * @param list<string>         $storedContacts
     *
     * @return list<Write>
     */
    private function contactLinksChanged(array $document, array $storedContacts): array
    {
        $id = self::idOf($document);
        $organization = OrganizationKeys::organization($id);
        $contacts = self::contactsOf($document);

        return [
            ...array_map(fn (string $contact): Write => $this->prospectingTable->putting(['PK' => OrganizationKeys::contact($contact), 'SK' => $organization, 'accountId' => self::accountOf($document), 'organizationId' => $id]), array_values(array_diff($contacts, $storedContacts))),
            ...array_map(fn (string $contact): Write => $this->prospectingTable->deleting(OrganizationKeys::contact($contact), $organization), array_values(array_diff($storedContacts, $contacts))),
        ];
    }

    /**
     * Returns the reservations of what designates one organization in its account: its SIRET, its links and its
     * website's domain. They go from the stored document to the one to store. An organization no longer prospected has
     * none.
     *
     * @param array<string, mixed>|null $document
     * @param array<string, mixed>|null $stored
     *
     * @return list<Write>
     */
    private function reservationsChanged(?array $document, ?array $stored): array
    {
        $owner = $document ?? $stored;

        if (null === $owner) {
            return [];
        }

        return [
            ...$this->reservationChanged(self::SIRET, $owner, self::siretOf($document), self::siretOf($stored), SiretAlreadyUsed::byAnother(...)),
            ...$this->referencesChanged($owner, null === $document ? [] : self::referenceKeysOf($document), null === $stored ? [] : self::referenceKeysOf($stored)),
            ...$this->reservationChanged(self::WEBSITE, $owner, self::domainOf($document), self::domainOf($stored), WebsiteAlreadyUsed::byAnother(...)),
        ];
    }

    /**
     * @param array<string, mixed>|null $document
     */
    private static function siretOf(?array $document): ?string
    {
        return null === $document ? null : Documents::legalIdentityOf($document['legalIdentity'])->siret?->value;
    }

    /**
     * Returns the reservation of a value that only one organization of the account may have. The new value is taken
     * when it changes, and the former one is released.
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
            ...(null === $value ? [] : [$this->prospectingTable->putting(['PK' => self::reservationKey($kind, self::accountOf($owner), $value), 'SK' => $kind, 'ownerId' => self::idOf($owner)], 'attribute_not_exists(PK) OR ownerId = :owner', [':owner' => self::idOf($owner)], $takenMeanwhile)]),
            ...(null === $stored ? [] : [$this->prospectingTable->deleting(self::reservationKey($kind, self::accountOf($owner), $stored), $kind)]),
        ];
    }

    /**
     * Returns the reservations of the links taken, and releases those of the links dropped.
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
            ...array_map(fn (string $key): Write => $this->prospectingTable->putting(['PK' => self::reservationKey(self::REFERENCE, self::accountOf($owner), $key), 'SK' => self::REFERENCE, 'ownerId' => self::idOf($owner)], 'attribute_not_exists(PK) OR ownerId = :owner', [':owner' => self::idOf($owner)], ReferenceAlreadyUsed::byAnother(...)), array_values(array_diff($keys, $storedKeys))),
            ...array_map(fn (string $key): Write => $this->prospectingTable->deleting(self::reservationKey(self::REFERENCE, self::accountOf($owner), $key), self::REFERENCE), array_values(array_diff($storedKeys, $keys))),
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

        $owner = null === $holder ? null : Typed::string($holder['ownerId']);

        return null === $owner || $owner === $except->value ? null : $this->get(new OrganizationId($owner));
    }

    /**
     * Returns the reservations of what designates the document's organization in its account.
     *
     * @param array<string, mixed> $document
     *
     * @return list<array<string, string>>
     */
    private static function reservationsOf(array $document): array
    {
        $reserved = [
            ...array_map(static fn (string $key): array => [self::REFERENCE, $key], self::referenceKeysOf($document)),
            ...array_map(static fn (string $domain): array => [self::WEBSITE, $domain], array_filter([self::domainOf($document)])),
        ];

        return array_map(static fn (array $reservation): array => ['PK' => self::reservationKey($reservation[0], self::accountOf($document), $reservation[1]), 'SK' => $reservation[0], 'ownerId' => self::idOf($document)], $reserved);
    }

    /**
     * @param array<string, mixed>|null $document
     */
    private static function domainOf(?array $document): ?string
    {
        $website = null === $document ? null : Typed::stringOrNull($document['website'] ?? null);

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
     * Writes its email domain's link when the domain changes, and deletes the former link. An email changed within its
     * domain keeps the link.
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
     * Writes its resemblance key's link when the key changes, and deletes the former link.
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
            ...(null === $key ? [] : [$this->prospectingTable->putting(self::resemblanceLink(self::accountOf($document), $key, self::idOf($document)))]),
            ...(null === $storedKey ? [] : [$this->prospectingTable->deleting(OrganizationKeys::ofResemblance(self::accountOf($document), $storedKey), OrganizationKeys::organization(self::idOf($document)))]),
        ];
    }

    /**
     * @return array{PK: string, SK: string, organizationId: string}
     */
    private static function resemblanceLink(string $accountId, string $key, string $organizationId): array
    {
        return ['PK' => OrganizationKeys::ofResemblance($accountId, $key), 'SK' => OrganizationKeys::organization($organizationId), 'organizationId' => $organizationId];
    }

    /**
     * @return array{PK: string, SK: string}
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
