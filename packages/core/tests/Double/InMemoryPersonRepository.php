<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\SearchTerms;
use Prospecter\Core\SharedKernel\Domain\Slice;

final readonly class InMemoryPersonRepository implements PersonRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(PersonId $id): Person
    {
        return $this->database->person((string) $id) ?? throw UnknownPerson::identifiedBy($id);
    }

    /**
     * @param list<PersonId> $ids
     *
     * @return list<Person>
     */
    public function ofIds(array $ids): array
    {
        return array_values(array_filter(array_map(fn (PersonId $id): ?Person => $this->database->person((string) $id), $ids)));
    }

    public function sliceOf(AccountId $accountId, Purpose $purpose, SearchTerms $search, ?string $after, int $max): Slice
    {
        $kept = array_values(array_filter($this->database->persons(), static fn (Person $person): bool => $person->accountId()->equals($accountId) && $person->purpose() === $purpose));

        return NewestFirst::slice($kept, static fn (Person $person): string => (string) $person->id(), static fn (Person $person): SearchTerms => $person->searchTerms(), $search, $after, $max);
    }

    public function remove(Person $person): void
    {
        $this->database->forgetPerson((string) $person->id());
    }

    public function isEmailUsed(AccountId $accountId, Purpose $purpose, Email $email, PersonId $except): bool
    {
        return array_any($this->database->persons(), fn (Person $person): bool => !$person->id()->equals($except) && $person->accountId()->equals($accountId) && $person->purpose() === $purpose && $person->contactDetails()->email?->value === $email->value);
    }

    public function save(Person $person): void
    {
        $this->database->storePerson($person);
    }
}
