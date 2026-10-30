<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\PersonId;

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
