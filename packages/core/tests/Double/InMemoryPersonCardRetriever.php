<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\Port\PersonCardRetriever;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonIntent;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Model\Reading;
use Prospecter\Core\PersonalData\Domain\Policy\Masking;
use Prospecter\Core\PersonalData\Domain\Policy\PersonPossibilities;
use Prospecter\Core\PersonalData\Domain\Policy\PersonReading;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Reads the persons the in-memory repository saved, as the adapter reads the items of the table.
 */
final readonly class InMemoryPersonCardRetriever implements PersonCardRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(PersonId $id, Reader $reader): PersonCard
    {
        $person = $this->database->person((string) $id);

        if (null === $person || !PersonReading::isReachableBy($reader, $person->accountId())) {
            throw UnknownPerson::identifiedBy($id);
        }

        return self::cardOf($person, $reader);
    }

    /**
     * @param list<PersonId> $ids
     */
    public function ofIds(array $ids, Reader $reader): array
    {
        return self::reachableCardsOf(array_filter(array_map(fn (PersonId $id): ?Person => $this->database->person((string) $id), $ids)), $reader);
    }

    public function contactsWithPhoneNumber(PhoneNumber $phoneNumber, Reader $reader): array
    {
        return self::reachableCardsOf(array_filter($this->database->persons(), static fn (Person $person): bool => Purpose::Prospecting === $person->purpose() && $person->contactDetails()->phoneNumber?->value === $phoneNumber->value), $reader);
    }

    /**
     * For a person the reader reaches.
     */
    public static function cardOf(Person $person, Reader $reader): PersonCard
    {
        $masked = Reading::Masked === PersonReading::by($reader, $person->id());
        $name = $person->name();
        $contact = $person->contactDetails();

        return new PersonCard(
            id: $person->id()->value,
            masked: $masked,
            firstName: $masked ? Masking::name($name->firstName) : $name->firstName,
            lastName: $masked ? Masking::name($name->lastName) : $name->lastName,
            jobTitle: $masked ? Masking::jobTitle($person->jobTitle()) : $person->jobTitle(),
            email: $masked ? Masking::email($contact->email?->value) : $contact->email?->value,
            phoneNumber: $masked ? Masking::phoneNumber($contact->phoneNumber) : $contact->phoneNumber?->international(),
            language: $contact->language,
            picture: $masked ? null : FileResponse::orNone($person->picture()),
            possibleIntents: array_map(static fn (PersonIntent $intent): string => $intent->name, PersonPossibilities::given($reader, $person->id(), $person->accountId(), $person->purpose())),
            createdAt: $person->createdAt(),
            modifiedAt: $person->modifiedAt(),
        );
    }

    /**
     * @param array<Person> $persons
     *
     * @return list<PersonCard>
     */
    private static function reachableCardsOf(array $persons, Reader $reader): array
    {
        $reachable = array_filter($persons, static fn (Person $person): bool => PersonReading::isReachableBy($reader, $person->accountId()));

        return array_values(array_map(static fn (Person $person): PersonCard => self::cardOf($person, $reader), $reachable));
    }
}
