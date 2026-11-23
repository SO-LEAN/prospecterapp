<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Application\Port\DesignatedContactRetriever;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonResemblanceKey;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Reads the contacts that the in-memory repository saved, by what designates them in their account. It reads them
 * the same way the adapter reads the reservations and indexes that the repository writes.
 */
final readonly class InMemoryDesignatedContactRetriever implements DesignatedContactRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function withReference(AccountId $accountId, ExternalReference $reference): ?PersonId
    {
        return array_find($this->contactsOf($accountId), static fn (Person $person): bool => $person->holdsReference($reference))?->id();
    }

    public function withEmail(AccountId $accountId, Email $email): ?PersonId
    {
        return array_find($this->contactsOf($accountId), static fn (Person $person): bool => $person->contactDetails()->email?->value === $email->value)?->id();
    }

    public function withPhoneNumber(AccountId $accountId, PhoneNumber $phoneNumber): array
    {
        $calling = array_filter($this->contactsOf($accountId), static fn (Person $person): bool => $person->contactDetails()->phoneNumber?->value === $phoneNumber->value);

        return array_values(array_map(static fn (Person $person): PersonId => $person->id(), $calling));
    }

    public function resembling(AccountId $accountId, PersonResemblanceKey $key): array
    {
        $resembling = array_filter($this->contactsOf($accountId), static fn (Person $person): bool => $person->resemblanceKey()?->value === $key->value);

        return array_values(array_map(static fn (Person $person): PersonId => $person->id(), $resembling));
    }

    /**
     * @return list<Person>
     */
    private function contactsOf(AccountId $accountId): array
    {
        return array_values(array_filter($this->database->persons(), static fn (Person $person): bool => $person->isAContactOf($accountId)));
    }
}
