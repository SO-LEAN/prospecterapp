<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\UnknownPerson;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class InMemoryPersonRepository implements PersonRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function nextIdentity(): PersonId
    {
        return new PersonId($this->database->uuids->next());
    }

    public function get(PersonId $id): Person
    {
        return $this->database->person((string) $id) ?? throw new UnknownPerson($id);
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

    public function save(Person $person): void
    {
        $this->database->storePerson($person);
    }
}
