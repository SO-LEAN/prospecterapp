<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonResemblanceKey;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReference;

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

    public function resembling(AccountId $accountId, PersonResemblanceKey $key, PersonId $except): array
    {
        $resembling = array_filter($this->database->persons(), static fn (Person $person): bool => !$person->id()->equals($except) && $person->accountId()->equals($accountId) && $person->resemblanceKey()?->value === $key->value);

        return array_values(array_map(static fn (Person $person): PersonId => $person->id(), $resembling));
    }

    public function isEmailUsed(AccountId $accountId, Purpose $purpose, Email $email, PersonId $except): bool
    {
        return array_any($this->database->persons(), fn (Person $person): bool => !$person->id()->equals($except) && $person->accountId()->equals($accountId) && $person->purpose() === $purpose && $person->contactDetails()->email?->value === $email->value);
    }

    public function contactsHavingEmails(AccountId $accountId, array $emails): array
    {
        $contacts = [];
        foreach ($this->database->persons() as $person) {
            $email = $person->contactDetails()->email;
            if ($person->isAContactOf($accountId) && null !== $email && in_array($email, $emails, false)) {
                $contacts[$email->value] = $person->id();
            }
        }

        return $contacts;
    }

    public function contactWithEmail(AccountId $accountId, Email $email): ?Person
    {
        return array_find($this->database->persons(), static fn (Person $person): bool => $person->isAContactOf($accountId) && $person->contactDetails()->email?->value === $email->value);
    }

    public function contactHoldingReference(AccountId $accountId, ExternalReference $reference, PersonId $except): ?Person
    {
        return array_find($this->database->persons(), static fn (Person $person): bool => !$person->id()->equals($except) && $person->isAContactOf($accountId) && $person->holdsReference($reference));
    }

    public function save(Person $person): void
    {
        $this->database->storePerson($person);
    }
}
