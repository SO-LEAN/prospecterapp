<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Entity\Person;
use Prospecter\Core\Exception\Gateway\NotFoundException;
use Prospecter\Core\Exception\Gateway\UniqueConstraintViolationException;

/**
 * The persons table shared by the in-memory gateways, with the constraints of the real one:
 * generated ids and an email unique among all persons (organizations and users alike). Like a database,
 * it keeps copies: a change is stored only when it is saved.
 */
final class InMemoryDatabase
{
    /** @var array<int, Person> */
    private array $persons = [];
    private int $lastId = 0;

    public function insert(Person $person): void
    {
        $this->assertEmailIsFree($person);
        $person->setId(++$this->lastId);
        $this->persons[$this->lastId] = clone $person;
    }

    public function replace(int $id, Person $person): void
    {
        $this->find($id) ?? throw new NotFoundException(sprintf('No person #%d', $id));
        $this->assertEmailIsFree($person);
        $person->setId($id);
        $this->persons[$id] = clone $person;
    }

    public function find(int $id): ?Person
    {
        return isset($this->persons[$id]) ? clone $this->persons[$id] : null;
    }

    /**
     * @template T of Person
     *
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    public function all(string $class): array
    {
        return array_values(array_map(
            static fn (Person $person): Person => clone $person,
            array_filter($this->persons, static fn (Person $person): bool => $person instanceof $class),
        ));
    }

    /**
     * Copies the rows, like the state a database transaction starts from.
     *
     * @return array{array<int, Person>, int}
     */
    public function snapshot(): array
    {
        return [array_map(static fn (Person $person): Person => clone $person, $this->persons), $this->lastId];
    }

    /**
     * @param array{array<int, Person>, int} $snapshot
     */
    public function restore(array $snapshot): void
    {
        [$this->persons, $this->lastId] = $snapshot;
    }

    private function assertEmailIsFree(Person $person): void
    {
        if (null === $person->getEmail()) {
            return;
        }

        foreach ($this->persons as $id => $other) {
            if ($id !== $person->getId() && $other->getEmail() === $person->getEmail()) {
                throw new UniqueConstraintViolationException(sprintf('Email "%s" is already used', $person->getEmail()));
            }
        }
    }
}
