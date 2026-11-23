<?php

declare(strict_types=1);

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\Revision;
use App\Shared\Persistence\SearchIndexes;
use App\Shared\Persistence\Table;
use App\Shared\Persistence\Write;
use App\Shared\Typed;
use Prospecter\Core\PersonalData\Domain\Exception\ReferenceTakenMeanwhile;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonResemblanceKey;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Contact\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReference;

/**
 * The persons in PersonalData's table (ADR 0048):
 * - `PERSON#<id>` / `PERSON`: the person, listed by account and purpose in `GSI1` (`ACCOUNT#<id>#<purpose>`,
 *   then its id);
 * - `EMAIL#<account>#<purpose>#<email>` / `EMAIL`: the email a person holds among the persons an account keeps
 *   for a purpose. It is EmailUniqueness's safety net (ADR 0039, 0048);
 * - `PERSON#<id>` / `FIRST_NAME`: the first name's key of a person who has one, in the `NAME` index next to
 *   their last name's key (ADR 0149). It is written and removed with the person;
 * - `REFERENCE#<account>#<source>#<identifier>` / `REFERENCE`: each link a contact holds, current or former.
 *   It is ReferenceUniqueness's safety net (ADR 0159);
 * - `RESEMBLANCE#<account>#<key>` / `PERSON#<id>`: the account's contacts that share a resemblance key. It is
 *   written and deleted with the contact (ADR 0170).
 * The person item carries its search keys and its phone number among the persons of the same account and purpose
 * (`SearchIndexes`). It also carries its `revision`, incremented at each save. The revision versions the person's
 * facts (ADR 0099), and the aggregate does not know it. A save or a removal holds to the revision that its unit of
 * work read (ADR 0181).
 */
final readonly class DynamoPersonRepository implements PersonRepository
{
    private const string EMAIL = PersonalDataKeys::EMAIL;
    private const string REFERENCE = PersonalDataKeys::REFERENCE;
    private const string FORMER_LINKEDIN = 'LINKEDIN';
    private const string RESEMBLANCE_KEY = 'resemblanceKey';

    public function __construct(private Table $personalDataTable)
    {
    }

    public function get(PersonId $id): Person
    {
        $item = $this->personalDataTable->item(PersonalDataKeys::person($id->value), PersonalDataKeys::PERSON) ?? throw UnknownPerson::identifiedBy($id);

        return PersonDocument::person($item['person']);
    }

    /**
     * @param list<PersonId> $ids
     */
    public function ofIds(array $ids): array
    {
        return $this->personsOf($this->personalDataTable->items(array_map(static fn (PersonId $id): array => [PersonalDataKeys::person($id->value), PersonalDataKeys::PERSON], $ids)));
    }

    public function remove(Person $person): void
    {
        $email = $person->contactDetails()->email?->value;

        $this->personalDataTable->transact([
            $this->personalDataTable->deletingRevised(PersonalDataKeys::person($person->id()->value), PersonalDataKeys::PERSON),
            ...(null === $person->searchKeys()->firstName ? [] : [$this->deletingTheFirstName($person)]),
            ...(null === $email ? [] : [$this->personalDataTable->deleting(self::emailKey($person->accountId(), $person->purpose(), $email), self::EMAIL)]),
            ...$this->releasing($person, $person->referencesHeld()),
            ...$this->resemblanceLinkChanged($person, null, $person->resemblanceKey()?->value),
        ]);
    }

    public function resembling(AccountId $accountId, PersonResemblanceKey $key, PersonId $except): array
    {
        $links = $this->personalDataTable->query(PersonalDataKeys::ofResemblance($accountId->value, $key->value));
        $resembling = array_map(static fn (array $link): PersonId => new PersonId(Typed::string($link['personId'])), $links);

        return array_values(array_filter($resembling, static fn (PersonId $id): bool => !$id->equals($except)));
    }

    public function isEmailUsed(AccountId $accountId, Purpose $purpose, Email $email, PersonId $except): bool
    {
        $holder = $this->personalDataTable->item(self::emailKey($accountId, $purpose, $email->value), self::EMAIL);

        return null !== $holder && Typed::string($holder['ownerId']) !== $except->value;
    }

    /**
     * Finds the contacts through their emails' reservations among the account's contacts. It does not read the persons.
     *
     * @param list<Email> $emails
     */
    public function contactsHavingEmails(AccountId $accountId, array $emails): array
    {
        $emailsByKey = array_combine(array_map(static fn (Email $email): string => self::emailKey($accountId, Purpose::Prospecting, $email->value), $emails), array_map(static fn (Email $email): string => $email->value, $emails));
        $reservations = $this->personalDataTable->items(array_map(static fn (string $key): array => [$key, self::EMAIL], array_keys($emailsByKey)));
        $contacts = [];
        foreach ($reservations as $reservation) {
            $contacts[$emailsByKey[Typed::string($reservation['PK'])]] = new PersonId(Typed::string($reservation['ownerId']));
        }

        return $contacts;
    }

    public function contactWithEmail(AccountId $accountId, Email $email): ?Person
    {
        $holder = Typed::stringOrNull($this->personalDataTable->item(self::emailKey($accountId, Purpose::Prospecting, $email->value), self::EMAIL)['ownerId'] ?? null);

        return null === $holder ? null : $this->get(new PersonId($holder));
    }

    public function contactHoldingReference(AccountId $accountId, ExternalReference $reference, PersonId $except): ?Person
    {
        $holder = Typed::stringOrNull($this->personalDataTable->item(self::referenceKey($accountId, $reference), self::REFERENCE)['ownerId'] ?? null);

        return null === $holder || $holder === $except->value ? null : $this->get(new PersonId($holder));
    }

    public function save(Person $person): void
    {
        $document = PersonDocument::of($person);
        $stored = $this->personalDataTable->item(PersonalDataKeys::person($person->id()->value), PersonalDataKeys::PERSON);
        $storedEmail = Typed::stringOrNull($stored['email'] ?? null);
        $storedPerson = null === $stored ? null : PersonDocument::person($stored['person']);
        $email = self::emailOf($document);
        $id = self::idOf($document);
        $writes = [
            $this->personalDataTable->revising($this->itemOf($person, $document, Revision::after($stored))),
            ...$this->firstNameWrites($person, $storedPerson),
        ];

        if ($email !== $storedEmail && null !== $email) {
            $writes[] = $this->personalDataTable->putting(['PK' => self::emailKey($person->accountId(), $person->purpose(), $email), 'SK' => self::EMAIL, 'ownerId' => $id], 'attribute_not_exists(PK) OR ownerId = :owner', [':owner' => $id], EmailAlreadyUsed::byAnother(...));
        }

        if ($email !== $storedEmail && null !== $storedEmail) {
            $writes[] = $this->personalDataTable->deleting(self::emailKey($person->accountId(), $person->purpose(), $storedEmail), self::EMAIL);
        }

        $this->personalDataTable->transact([...$writes, ...$this->referenceWrites($person, $storedPerson), ...$this->resemblanceLinkChanged($person, $person->resemblanceKey()?->value, Typed::stringOrNull($stored[self::RESEMBLANCE_KEY] ?? null))]);
    }

    /**
     * Reserves each reference that a contact now holds and did not hold before, on condition that no other contact
     * holds it. This is ReferenceUniqueness's safety net. It also releases the references the contact no longer holds.
     * A member's profile reserves nothing.
     *
     * @return list<Write>
     */
    private function referenceWrites(Person $person, ?Person $stored): array
    {
        if (!self::isAContact($person)) {
            return [];
        }
        $taken = array_filter($person->referencesHeld(), static fn (ExternalReference $reference): bool => !($stored?->holdsReference($reference) ?? false));
        $dropped = array_filter($stored?->referencesHeld() ?? [], static fn (ExternalReference $reference): bool => !$person->holdsReference($reference));

        return [...array_values(array_map(fn (ExternalReference $reference): Write => $this->reserving($person, $reference), $taken)), ...$this->releasing($person, array_values($dropped))];
    }

    private function reserving(Person $person, ExternalReference $reference): Write
    {
        return $this->personalDataTable->putting(
            ['PK' => self::referenceKey($person->accountId(), $reference), 'SK' => self::REFERENCE, 'ownerId' => $person->id()->value],
            'attribute_not_exists(PK) OR ownerId = :owner',
            [':owner' => $person->id()->value],
            ReferenceTakenMeanwhile::byAnother(...),
        );
    }

    /**
     * Deletes the reservations of these references that the person held when the unit of work read it. A reservation
     * that another contact holds stays theirs. That contact is a duplicate logged before the references were unique.
     *
     * @param list<ExternalReference> $references
     *
     * @return list<Write>
     */
    private function releasing(Person $person, array $references): array
    {
        $reservations = $this->personalDataTable->items(array_map(static fn (ExternalReference $reference): array => [self::referenceKey($person->accountId(), $reference), self::REFERENCE], $references));
        $own = array_filter($reservations, static fn (array $reservation): bool => $reservation['ownerId'] === $person->id()->value);

        return array_values(array_map(fn (array $reservation): Write => $this->personalDataTable->deleting(Typed::string($reservation['PK']), self::REFERENCE, 'ownerId = :owner', [':owner' => $person->id()->value]), $own));
    }

    /**
     * Writes every person's item again, with its search keys and its key in its list. It also writes the person's
     * first name item, the reservation of each reference a contact holds, and their resemblance key link (ADR 0170).
     * It deletes the search items from before ADR 0071 and the LinkedIn profiles' reservations from before
     * references existed. A table written before ADR 0071, ADR 0149 or ADR 0170, or before the references were unique, needs
     * this once.
     */
    public function writeTheKeysAgain(): int
    {
        $stored = array_values(array_filter($this->personalDataTable->scan(PersonalDataKeys::PERSON.'#'), static fn (array $item): bool => PersonalDataKeys::PERSON === $item['SK']));
        $items = array_map(static fn (array $item): array => ['person' => PersonDocument::recognizedAgain(Typed::document($item['person']))] + $item, $stored);
        $persons = array_map(static fn (array $item): Person => PersonDocument::person($item['person']), $items);
        $reservations = $this->referenceReservationsOf($persons);
        $this->personalDataTable->batch(
            [
                ...array_map(fn (Person $person, array $item): array => $this->itemOf($person, $item['person'], Revision::of($item)), $persons, $items),
                ...array_filter(array_map($this->firstNameItemOf(...), $persons)),
                ...$reservations,
                ...self::resemblanceLinksOf($persons),
            ],
            array_map(static fn (array $former): array => [Typed::string($former['PK']), Typed::string($former['SK'])], [...$this->personalDataTable->scan('TOKEN'), ...$this->personalDataTable->scan(self::FORMER_LINKEDIN.'#'), ...$this->referencesNoLongerHeld($reservations)]),
        );

        return count($items);
    }

    /**
     * Finds the link reservations that no contact holds any more, once the links are recognized again under another
     * reference.
     *
     * @param list<array<string, string>> $reservations
     *
     * @return list<array<string, mixed>>
     */
    private function referencesNoLongerHeld(array $reservations): array
    {
        $held = array_column($reservations, 'PK');

        return array_values(array_filter($this->personalDataTable->scan(self::REFERENCE.'#'), static fn (array $reservation): bool => !in_array($reservation['PK'], $held, true)));
    }

    /**
     * Reserves each reference a contact holds. Two contacts logged before the references were unique may hold the same
     * reference. The reservation then goes to the oldest of them, the first id in order (UUID v7).
     *
     * @param list<Person> $persons
     *
     * @return list<array<string, string>>
     */
    private function referenceReservationsOf(array $persons): array
    {
        $contacts = array_filter($persons, self::isAContact(...));
        usort($contacts, static fn (Person $one, Person $other): int => strcmp($one->id()->value, $other->id()->value));
        $reservations = [];

        foreach ($contacts as $contact) {
            foreach ($contact->referencesHeld() as $reference) {
                $key = self::referenceKey($contact->accountId(), $reference);
                $reservations[$key] ??= ['PK' => $key, 'SK' => self::REFERENCE, 'ownerId' => $contact->id()->value];
            }
        }

        return array_values($reservations);
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array{PK: string, SK: string, ...}
     */
    private function itemOf(Person $person, array $document, int $revision): array
    {
        $id = self::idOf($document);

        return [
            'PK' => PersonalDataKeys::person($id),
            'SK' => PersonalDataKeys::PERSON,
            'GSI1PK' => PersonalDataKeys::listOf(new AccountId(Typed::string($document['accountId'])), Purpose::from(Typed::string($document['purpose']))),
            'GSI1SK' => $id,
            'email' => self::emailOf($document),
            self::RESEMBLANCE_KEY => $person->resemblanceKey()?->value,
            Revision::ATTRIBUTE => $revision,
            'person' => $document,
            ...SearchIndexes::of(PersonalDataKeys::scopeOf($person->accountId(), $person->purpose()), $person->searchKeys(), $person->contactDetails()->phoneNumber),
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
    private static function emailOf(array $document): ?string
    {
        return Typed::stringOrNull(Typed::document($document['contactDetails'])['email'] ?? null);
    }

    /**
     * Puts the first name item when the person has a first name. Deletes it when the stored person had one and the
     * person no longer has. Writes nothing otherwise. A deletion is also a write of the transaction.
     *
     * @return list<Write>
     */
    private function firstNameWrites(Person $person, ?Person $stored): array
    {
        $item = $this->firstNameItemOf($person);

        if (null !== $item) {
            return [$this->personalDataTable->putting($item)];
        }

        return null === $stored?->searchKeys()->firstName ? [] : [$this->deletingTheFirstName($person)];
    }

    /**
     * Writes the resemblance key link when the key changes, and deletes the former link.
     *
     * @return list<Write>
     */
    private function resemblanceLinkChanged(Person $person, ?string $key, ?string $storedKey): array
    {
        if ($key === $storedKey) {
            return [];
        }

        return [
            ...(null === $key ? [] : [$this->personalDataTable->putting(self::resemblanceLink($person->accountId()->value, $key, $person->id()->value))]),
            ...(null === $storedKey ? [] : [$this->personalDataTable->deleting(PersonalDataKeys::ofResemblance($person->accountId()->value, $storedKey), PersonalDataKeys::person($person->id()->value))]),
        ];
    }

    /**
     * Returns the resemblance key link of each person who has a key: a contact with a first and a last name.
     *
     * @param list<Person> $persons
     *
     * @return list<array<string, string>>
     */
    private static function resemblanceLinksOf(array $persons): array
    {
        $resembling = array_values(array_filter($persons, static fn (Person $person): bool => null !== $person->resemblanceKey()));

        return array_map(static fn (Person $contact): array => self::resemblanceLink($contact->accountId()->value, (string) $contact->resemblanceKey(), $contact->id()->value), $resembling);
    }

    /**
     * @return array{PK: string, SK: string, personId: string}
     */
    private static function resemblanceLink(string $accountId, string $key, string $personId): array
    {
        return ['PK' => PersonalDataKeys::ofResemblance($accountId, $key), 'SK' => PersonalDataKeys::person($personId), 'personId' => $personId];
    }

    private function deletingTheFirstName(Person $person): Write
    {
        return $this->personalDataTable->deleting(PersonalDataKeys::person($person->id()->value), PersonalDataKeys::FIRST_NAME);
    }

    /**
     * @return array{PK: string, SK: string, ...}|null
     */
    private function firstNameItemOf(Person $person): ?array
    {
        $firstName = $person->searchKeys()->firstName;

        return null === $firstName ? null : SearchIndexes::inTheNames(PersonalDataKeys::scopeOf($person->accountId(), $person->purpose()), $firstName) + [
            'PK' => PersonalDataKeys::person($person->id()->value),
            'SK' => PersonalDataKeys::FIRST_NAME,
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return list<Person>
     */
    private function personsOf(array $items): array
    {
        return array_map(static fn (array $item): Person => PersonDocument::person($item['person']), $items);
    }

    private static function emailKey(AccountId $accountId, Purpose $purpose, string $email): string
    {
        return PersonalDataKeys::email($accountId, $purpose, $email);
    }

    private static function isAContact(Person $person): bool
    {
        return $person->isAContactOf($person->accountId());
    }

    private static function referenceKey(AccountId $accountId, ExternalReference $reference): string
    {
        return PersonalDataKeys::reference($accountId, $reference);
    }
}
