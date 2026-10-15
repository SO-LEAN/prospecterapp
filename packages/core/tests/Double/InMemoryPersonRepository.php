<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Page;
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

    public function pageOf(AccountId $accountId, Purpose $purpose, string $text, int $page, int $maxByPage): Page
    {
        $matching = array_values(array_filter($this->database->persons(), static fn (Person $person): bool => $person->accountId()->equals($accountId) && $person->purpose() === $purpose && self::matches($person, mb_strtolower($text))));
        usort($matching, static fn (Person $a, Person $b): int => strcmp((string) $b->id(), (string) $a->id()));
        $total = count($matching);

        return new Page($page, $total, intdiv($total - 1, $maxByPage) + 1, array_slice($matching, ($page - 1) * $maxByPage, $maxByPage));
    }

    public function remove(Person $person): void
    {
        $this->database->forgetPerson((string) $person->id());
    }

    private static function matches(Person $person, string $text): bool
    {
        $searchable = [$person->name()->firstName, $person->name()->lastName, $person->contactDetails()->email?->value];

        return '' === $text || array_any($searchable, static fn (?string $value): bool => str_contains(mb_strtolower((string) $value), $text));
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
