<?php

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\Documents;
use App\Shared\Persistence\SearchIndexes;
use App\Shared\Persistence\Table;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\SearchKey;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * The persons in the table of PersonalData (ADR 0048):
 * - `PERSON#<id>` / `PERSON`: the person, listed by account and purpose in `GSI1` (`ACCOUNT#<id>#<purpose>`,
 *   its id);
 * - `EMAIL#<account>#<purpose>#<email>` / `EMAIL`: the email a person holds among the persons an account keeps
 *   for a purpose, the safety net of EmailUniqueness (ADR 0039).
 * The person carries its search keys and its phone number among the persons of the same account and purpose
 * (`SearchIndexes`).
 */
final readonly class DynamoPersonRepository implements PersonRepository
{
    private const string PERSON = 'PERSON';
    private const string EMAIL = 'EMAIL';

    public function __construct(private Table $personalDataTable)
    {
    }

    public function get(PersonId $id): Person
    {
        $item = $this->personalDataTable->item(self::keyOf($id), self::PERSON) ?? throw UnknownPerson::identifiedBy($id);

        return PersonDocument::person($item['person']);
    }

    /**
     * @param list<PersonId> $ids
     */
    public function ofIds(array $ids): array
    {
        return $this->personsOf($this->personalDataTable->items(array_map(static fn (PersonId $id): array => [self::keyOf($id), self::PERSON], $ids)));
    }

    /**
     * Without a search, the account lists its persons in GSI1; a search asks the indexes of the search (ADR 0071).
     */
    public function sliceOf(AccountId $accountId, Purpose $purpose, ?SearchKey $search, ?string $after, int $max): Slice
    {
        $found = null === $search
            ? $this->personsOf($this->personalDataTable->query(self::listKey($accountId, $purpose), Table::BY_GSI1, $after, $max + 1))
            : $this->inTheOrderOf(SearchIndexes::found($this->personalDataTable, self::scopeOf($accountId, $purpose), $search, null === $after ? null : self::keyOf(new PersonId($after)), $max + 1));

        return Documents::slice($found, $max, static fn (Person $person): string => (string) $person->id());
    }

    /**
     * @param list<string> $keys partition keys of persons
     *
     * @return list<Person>
     */
    private function inTheOrderOf(array $keys): array
    {
        $persons = [];

        foreach ($this->personsOf($this->personalDataTable->items(array_map(static fn (string $key): array => [$key, self::PERSON], $keys))) as $person) {
            $persons[self::keyOf($person->id())] = $person;
        }

        return array_values(array_filter(array_map(static fn (string $key): ?Person => $persons[$key] ?? null, $keys)));
    }

    public function withPhoneNumber(AccountId $accountId, Purpose $purpose, PhoneNumber $phoneNumber): array
    {
        return $this->inTheOrderOf(SearchIndexes::withPhoneNumber($this->personalDataTable, self::scopeOf($accountId, $purpose), $phoneNumber));
    }

    public function remove(Person $person): void
    {
        $email = $person->contactDetails()->email?->value;

        $this->personalDataTable->transact([
            $this->personalDataTable->deleting(self::keyOf($person->id()), self::PERSON),
            ...(null === $email ? [] : [$this->personalDataTable->deleting(self::emailKey($person->accountId(), $person->purpose(), $email), self::EMAIL)]),
        ]);
    }

    public function isEmailUsed(AccountId $accountId, Purpose $purpose, Email $email, PersonId $except): bool
    {
        $holder = $this->personalDataTable->item(self::emailKey($accountId, $purpose, $email->value), self::EMAIL);

        return null !== $holder && $holder['ownerId'] !== $except->value;
    }

    public function save(Person $person): void
    {
        $document = PersonDocument::of($person);
        $storedEmail = $this->personalDataTable->item(self::keyOf($person->id()), self::PERSON)['email'] ?? null;
        $email = $document['contactDetails']['email'];
        $writes = [$this->personalDataTable->putting($this->itemOf($person, $document))];

        $reservation = null;

        if ($email !== $storedEmail && null !== $email) {
            $reservation = count($writes);
            $writes[] = $this->personalDataTable->putting(['PK' => self::emailKey($person->accountId(), $person->purpose(), $email), 'SK' => self::EMAIL, 'ownerId' => $document['id']], 'attribute_not_exists(PK) OR ownerId = :owner', [':owner' => $document['id']]);
        }

        if ($email !== $storedEmail && null !== $storedEmail) {
            $writes[] = $this->personalDataTable->deleting(self::emailKey($person->accountId(), $person->purpose(), $storedEmail), self::EMAIL);
        }

        try {
            $this->personalDataTable->transact($writes);
        } catch (ConditionFailed $taken) {
            throw null !== $reservation && $taken->failed($reservation) ? EmailAlreadyUsed::byAnother($taken) : $taken;
        }
    }

    /**
     * Writes again the search keys of every person, and forgets the items of the former search (ADR 0050):
     * what a table written before ADR 0071 needs once.
     */
    public function writeTheSearchKeysAgain(): int
    {
        $items = $this->personalDataTable->scan(self::PERSON.'#');
        $this->personalDataTable->batch(
            array_map(fn (array $item): array => $this->itemOf(PersonDocument::person($item['person']), $item['person']), $items),
            array_map(static fn (array $former): array => [$former['PK'], $former['SK']], $this->personalDataTable->scan('TOKEN')),
        );

        return count($items);
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    private function itemOf(Person $person, array $document): array
    {
        return SearchIndexes::of(self::scopeOf($person->accountId(), $person->purpose()), $person->searchKeys(), $person->contactDetails()->phoneNumber) + [
            'PK' => self::keyOf(new PersonId($document['id'])),
            'SK' => self::PERSON,
            'GSI1PK' => self::listKey(new AccountId($document['accountId']), Purpose::from($document['purpose'])),
            'GSI1SK' => $document['id'],
            'email' => $document['contactDetails']['email'],
            'person' => $document,
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

    private static function keyOf(PersonId $id): string
    {
        return self::PERSON.'#'.$id->value;
    }

    /**
     * What a person is searched among: the persons their account keeps for the same purpose.
     */
    private static function scopeOf(AccountId $accountId, Purpose $purpose): string
    {
        return $accountId->value.'#'.$purpose->value;
    }

    private static function listKey(AccountId $accountId, Purpose $purpose): string
    {
        return sprintf('ACCOUNT#%s#%s', $accountId->value, $purpose->value);
    }

    private static function emailKey(AccountId $accountId, Purpose $purpose, string $email): string
    {
        return sprintf('%s#%s#%s#%s', self::EMAIL, $accountId->value, $purpose->value, $email);
    }
}
