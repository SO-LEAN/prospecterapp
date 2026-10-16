<?php

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\Documents;
use App\Shared\Persistence\SearchProjection;
use App\Shared\Persistence\Table;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\SearchTerms;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * The persons in the table of PersonalData (ADR 0048):
 * - `PERSON#<id>` / `PERSON`: the person, listed by account and purpose in `GSI1` (`ACCOUNT#<id>#<purpose>`,
 *   its id);
 * - `EMAIL#<account>#<purpose>#<email>` / `EMAIL`: the email a person holds among the persons an account keeps
 *   for a purpose, the safety net of EmailUniqueness (ADR 0039).
 */
final readonly class DynamoPersonRepository implements PersonRepository
{
    private const string PERSON = 'PERSON';
    private const string EMAIL = 'EMAIL';

    public function __construct(private Table $personalDataTable, private SearchProjection $personalDataSearch)
    {
    }

    /**
     * What a person is searched among: the persons their account keeps for the same purpose.
     */
    public static function scopeOf(AccountId $accountId, Purpose $purpose): string
    {
        return $accountId->value.'#'.$purpose->value;
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
     * Without a search, the account lists its persons in GSI1; a search asks the projection (ADR 0050).
     */
    public function sliceOf(AccountId $accountId, Purpose $purpose, SearchTerms $search, ?string $after, int $max): Slice
    {
        $found = $search->isEmpty()
            ? $this->personsOf($this->personalDataTable->query(self::listKey($accountId, $purpose), Table::BY_GSI1, $after, $max + 1))
            : $this->inTheOrderOf(array_slice($this->personalDataSearch->find(self::scopeOf($accountId, $purpose), $search, $after), 0, $max + 1));

        return Documents::slice($found, $max, static fn (Person $person): string => (string) $person->id());
    }

    /**
     * @param list<string> $ids
     *
     * @return list<Person>
     */
    private function inTheOrderOf(array $ids): array
    {
        $persons = [];

        foreach ($this->ofIds(array_map(static fn (string $id): PersonId => new PersonId($id), $ids)) as $person) {
            $persons[(string) $person->id()] = $person;
        }

        return array_values(array_filter(array_map(static fn (string $id): ?Person => $persons[$id] ?? null, $ids)));
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
        $writes = [$this->personalDataTable->putting($this->itemOf($document))];

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
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    private function itemOf(array $document): array
    {
        return [
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

    private static function listKey(AccountId $accountId, Purpose $purpose): string
    {
        return sprintf('ACCOUNT#%s#%s', $accountId->value, $purpose->value);
    }

    private static function emailKey(AccountId $accountId, Purpose $purpose, string $email): string
    {
        return sprintf('%s#%s#%s#%s', self::EMAIL, $accountId->value, $purpose->value, $email);
    }
}
