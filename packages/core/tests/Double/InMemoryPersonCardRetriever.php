<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\Port\PersonCardRetriever;
use Prospecter\Core\PersonalData\Application\PossibleDuplicate;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonIntent;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Model\Reading;
use Prospecter\Core\PersonalData\Domain\Policy\Masking;
use Prospecter\Core\PersonalData\Domain\Policy\PersonPossibilities;
use Prospecter\Core\PersonalData\Domain\Policy\PersonReading;
use Prospecter\Core\SharedKernel\Application\ExternalLink;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\Origin;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Reads the persons that the in-memory repository saved, as the adapter reads the table's items.
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

        return self::cardOf($person, $reader, PersonReading::showsPossibleDuplicatesTo($reader) ? $this->possibleDuplicatesOf($person) : []);
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
     * The contacts that the person may duplicate, with the names these contacts keep. A forgotten contact is named
     * by its id. So a suspicion that the core failed to lift shows.
     *
     * @return list<PossibleDuplicate>
     */
    private function possibleDuplicatesOf(Person $person): array
    {
        return array_map(function (PersonId $id): PossibleDuplicate {
            $name = null === ($duplicate = $this->database->person($id->value)) ? new PersonName(lastName: $id->value) : $duplicate->name();

            return new PossibleDuplicate($id->value, $name->firstName, $name->lastName);
        }, StateOf::the($person)->listOf('possibleDuplicates', PersonId::class));
    }

    /**
     * The person must be one the reader reaches. The caller reads their possible duplicates separately. The contact
     * list shows none.
     *
     * @param list<PossibleDuplicate> $possibleDuplicates
     */
    public static function cardOf(Person $person, Reader $reader, array $possibleDuplicates = []): PersonCard
    {
        $state = StateOf::the($person);
        $masked = Reading::Masked === PersonReading::by($reader, $person->id());
        $nameMasked = Reading::Masked === PersonReading::ofTheName($reader, $person->id(), $person->purpose());
        $name = $state->object('name', PersonName::class);
        $contact = $person->contactDetails();

        return new PersonCard(
            id: $person->id()->value,
            masked: $masked,
            firstName: $nameMasked ? Masking::name($name->firstName) : $name->firstName,
            lastName: $nameMasked ? Masking::name($name->lastName) : $name->lastName,
            civility: $person->civility()->value,
            jobTitle: $masked ? Masking::jobTitle($state->stringOrNull('jobTitle')) : $state->stringOrNull('jobTitle'),
            email: $masked ? Masking::email($contact->email?->value) : $contact->email?->value,
            phoneNumber: $masked ? Masking::phoneNumber($contact->phoneNumber) : $contact->phoneNumber?->international(),
            language: $contact->language,
            links: $masked ? array_map(ExternalLink::masked(...), Masking::references($person->references())) : ExternalLink::allOf($person->references()),
            possibleDuplicates: $possibleDuplicates,
            picture: $masked ? null : FileResponse::orNone($state->objectOrNull('picture', File::class)),
            possibleIntents: array_map(static fn (PersonIntent $intent): string => $intent->name, PersonPossibilities::given($reader, $person->id(), $person->accountId(), $person->purpose())),
            createdAt: $state->object('createdAt', \DateTimeImmutable::class),
            modifiedAt: $state->object('modifiedAt', \DateTimeImmutable::class),
            createdByAnAI: Origin::Ai === $state->object('origin', Origin::class),
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
