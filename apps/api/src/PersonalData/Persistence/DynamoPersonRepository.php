<?php

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\Revision;
use App\Shared\Persistence\SearchIndexes;
use App\Shared\Persistence\Table;
use App\Shared\Persistence\Write;
use Prospecter\Core\PersonalData\Domain\Exception\ReferenceTakenMeanwhile;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * The persons in the table of PersonalData (ADR 0048):
 * - `PERSON#<id>` / `PERSON`: the person, listed by account and purpose in `GSI1` (`ACCOUNT#<id>#<purpose>`,
 *   its id);
 * - `EMAIL#<account>#<purpose>#<email>` / `EMAIL`: the email a person holds among the persons an account keeps
 *   for a purpose, the safety net of EmailUniqueness (ADR 0039, 0048).
 * - `PERSON#<id>` / `FIRST_NAME`: the key of the first name of a person who has one, in the index `NAME` beside
 *   the key of their last name (ADR 0149), written and removed with the person;
 * - `REFERENCE#<account>#<source>#<identifier>` / `REFERENCE`: each link a contact holds, theirs or a former one,
 *   the safety net of ReferenceUniqueness (ADR 0159).
 * The person carries its search keys and its phone number among the persons of the same account and purpose
 * (`SearchIndexes`), and its `revision`, incremented at each save: the revision of its facts (ADR 0099), which
 * the aggregate does not know.
 */
final readonly class DynamoPersonRepository implements PersonRepository
{
    private const string EMAIL = 'EMAIL';
    private const string REFERENCE = 'REFERENCE';
    private const string FORMER_LINKEDIN = 'LINKEDIN';

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
            $this->personalDataTable->deleting(PersonalDataKeys::person($person->id()->value), PersonalDataKeys::PERSON),
            ...(null === $person->searchKeys()->firstName ? [] : [$this->deletingTheFirstName($person)]),
            ...(null === $email ? [] : [$this->personalDataTable->deleting(self::emailKey($person->accountId(), $person->purpose(), $email), self::EMAIL)]),
            ...$this->releasing($person, $person->referencesHeld()),
        ]);
    }

    public function isEmailUsed(AccountId $accountId, Purpose $purpose, Email $email, PersonId $except): bool
    {
        $holder = $this->personalDataTable->item(self::emailKey($accountId, $purpose, $email->value), self::EMAIL);

        return null !== $holder && $holder['ownerId'] !== $except->value;
    }

    /**
     * Found by the reservations of their emails among the contacts of the account, without reading the persons.
     *
     * @param list<Email> $emails
     */
    public function contactsHavingEmails(AccountId $accountId, array $emails): array
    {
        $emailsByKey = array_combine(array_map(static fn (Email $email): string => self::emailKey($accountId, Purpose::Prospecting, $email->value), $emails), array_map(static fn (Email $email): string => $email->value, $emails));
        $reservations = $this->personalDataTable->items(array_map(static fn (string $key): array => [$key, self::EMAIL], array_keys($emailsByKey)));
        $contacts = [];
        foreach ($reservations as $reservation) {
            $contacts[$emailsByKey[$reservation['PK']]] = new PersonId($reservation['ownerId']);
        }

        return $contacts;
    }

    public function contactWithEmail(AccountId $accountId, Email $email): ?Person
    {
        $holder = $this->personalDataTable->item(self::emailKey($accountId, Purpose::Prospecting, $email->value), self::EMAIL)['ownerId'] ?? null;

        return null === $holder ? null : $this->get(new PersonId($holder));
    }

    public function contactWithPhoneNumber(AccountId $accountId, PhoneNumber $phoneNumber): ?Person
    {
        $key = SearchIndexes::withPhoneNumber($this->personalDataTable, PersonalDataKeys::scopeOf($accountId, Purpose::Prospecting), $phoneNumber)[0] ?? null;
        $document = null === $key ? null : $this->personalDataTable->item($key, PersonalDataKeys::PERSON)['person'] ?? null;

        return null === $document ? null : PersonDocument::person($document);
    }

    public function contactHoldingReference(AccountId $accountId, ExternalReference $reference, PersonId $except): ?Person
    {
        $holder = $this->personalDataTable->item(self::referenceKey($accountId, $reference), self::REFERENCE)['ownerId'] ?? null;

        return null === $holder || $holder === $except->value ? null : $this->get(new PersonId($holder));
    }

    public function save(Person $person): void
    {
        $document = PersonDocument::of($person);
        $stored = $this->personalDataTable->item(PersonalDataKeys::person($person->id()->value), PersonalDataKeys::PERSON);
        $storedEmail = $stored['email'] ?? null;
        $email = $document['contactDetails']['email'];
        $writes = [
            $this->personalDataTable->putting($this->itemOf($person, $document, Revision::after($stored))),
            ...$this->firstNameWrites($person, null === $stored ? null : PersonDocument::person($stored['person'])),
        ];

        if ($email !== $storedEmail && null !== $email) {
            $writes[] = $this->personalDataTable->putting(['PK' => self::emailKey($person->accountId(), $person->purpose(), $email), 'SK' => self::EMAIL, 'ownerId' => $document['id']], 'attribute_not_exists(PK) OR ownerId = :owner', [':owner' => $document['id']], EmailAlreadyUsed::byAnother(...));
        }

        if ($email !== $storedEmail && null !== $storedEmail) {
            $writes[] = $this->personalDataTable->deleting(self::emailKey($person->accountId(), $person->purpose(), $storedEmail), self::EMAIL);
        }

        $this->personalDataTable->transact([...$writes, ...$this->referenceWrites($person, null === $stored ? null : PersonDocument::person($stored['person']))]);
    }

    /**
     * The reservation of each reference a contact holds and did not before, under the condition that no other contact
     * holds it: the safety net of ReferenceUniqueness; and the release of those it held and no longer does. The
     * profile of a member is reserved for nobody.
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
     * The deletes of the reservations of these references the person holds, read in the unit before: one another
     * contact holds, a duplicate noted before the references were unique, stays theirs.
     *
     * @param list<ExternalReference> $references
     *
     * @return list<Write>
     */
    private function releasing(Person $person, array $references): array
    {
        $reservations = $this->personalDataTable->items(array_map(static fn (ExternalReference $reference): array => [self::referenceKey($person->accountId(), $reference), self::REFERENCE], $references));
        $own = array_filter($reservations, static fn (array $reservation): bool => $reservation['ownerId'] === $person->id()->value);

        return array_values(array_map(fn (array $reservation): Write => $this->personalDataTable->deleting($reservation['PK'], self::REFERENCE, 'ownerId = :owner', [':owner' => $person->id()->value]), $own));
    }

    /**
     * Writes again the item of every person, its search keys and its key in its list with it, the item of their first
     * name beside it, the reservation of each reference a contact holds, and forgets the items of the search before
     * ADR 0071 and the reservations of the LinkedIn profiles before the references: what a table written before ADR
     * 0071, before ADR 0149, or before the references were unique, needs once.
     */
    public function writeTheKeysAgain(): int
    {
        $stored = array_values(array_filter($this->personalDataTable->scan(PersonalDataKeys::PERSON.'#'), static fn (array $item): bool => PersonalDataKeys::PERSON === $item['SK']));
        $items = array_map(static fn (array $item): array => ['person' => PersonDocument::recognizedAgain($item['person'])] + $item, $stored);
        $persons = array_map(static fn (array $item): Person => PersonDocument::person($item['person']), $items);
        $reservations = $this->referenceReservationsOf($persons);
        $this->personalDataTable->batch(
            [
                ...array_map(fn (Person $person, array $item): array => $this->itemOf($person, $item['person'], Revision::of($item)), $persons, $items),
                ...array_filter(array_map($this->firstNameItemOf(...), $persons)),
                ...$reservations,
            ],
            array_map(static fn (array $former): array => [$former['PK'], $former['SK']], [...$this->personalDataTable->scan('TOKEN'), ...$this->personalDataTable->scan(self::FORMER_LINKEDIN.'#'), ...$this->referencesNoLongerHeld($reservations)]),
        );

        return count($items);
    }

    /**
     * The reservations of links no contact holds any more, once recognized again under another reference.
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
     * The reservation of each reference a contact holds; one two contacts noted before the references were unique for
     * the oldest of them, the first of the identities in their order (UUID v7).
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
     * @return array<string, mixed>
     */
    private function itemOf(Person $person, array $document, int $revision): array
    {
        return SearchIndexes::of(PersonalDataKeys::scopeOf($person->accountId(), $person->purpose()), $person->searchKeys(), $person->contactDetails()->phoneNumber) + [
            'PK' => PersonalDataKeys::person($document['id']),
            'SK' => PersonalDataKeys::PERSON,
            'GSI1PK' => PersonalDataKeys::listOf(new AccountId($document['accountId']), Purpose::from($document['purpose'])),
            'GSI1SK' => $document['id'],
            'email' => $document['contactDetails']['email'],
            Revision::ATTRIBUTE => $revision,
            'person' => $document,
        ];
    }

    /**
     * The item of the first name put when the person has one, deleted when the person stored had one and no longer
     * has, nothing otherwise: a deletion is a write of the transaction too.
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

    private function deletingTheFirstName(Person $person): Write
    {
        return $this->personalDataTable->deleting(PersonalDataKeys::person($person->id()->value), PersonalDataKeys::FIRST_NAME);
    }

    /**
     * @return array<string, string>|null
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
        return sprintf('%s#%s#%s#%s', self::EMAIL, $accountId->value, $purpose->value, $email);
    }

    private static function isAContact(Person $person): bool
    {
        return $person->isAContactOf($person->accountId());
    }

    private static function referenceKey(AccountId $accountId, ExternalReference $reference): string
    {
        return sprintf('%s#%s#%s', self::REFERENCE, $accountId->value, $reference->key());
    }
}
