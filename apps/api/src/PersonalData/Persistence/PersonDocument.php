<?php

declare(strict_types=1);

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Typed;
use Prospecter\Core\PersonalData\Domain\Model\Civility;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\KnownSource;
use Prospecter\Core\SharedKernel\Domain\Origin;

/**
 * Turns a person into a plain document for its DynamoDB item, and back (ADR 0048). It keeps the value objects as
 * they are, and never the recorded events. A person written before the origin was kept gets the origin "by hand".
 */
final class PersonDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function of(Person $person): array
    {
        $state = Hydrator::stateOf($person);
        $name = Typed::object($state['name'], PersonName::class);

        return [
            'id' => Typed::object($state['id'], PersonId::class)->value,
            'accountId' => Typed::object($state['accountId'], AccountId::class)->value,
            'purpose' => Typed::object($state['purpose'], Purpose::class)->value,
            'name' => ['firstName' => $name->firstName, 'lastName' => $name->lastName],
            'contactDetails' => Documents::contactDetails(Typed::object($state['contactDetails'], ContactDetails::class)),
            'references' => Documents::references(Typed::object($state['references'], ExternalReferences::class)),
            'formerReferences' => Documents::references(ExternalReferences::of(Typed::objects($state['formerReferences'], ExternalReference::class))),
            'jobTitle' => Typed::stringOrNull($state['jobTitle']),
            'title' => Typed::object($state['civility'], Civility::class)->value,
            'picture' => Documents::file(Typed::objectOrNull($state['picture'], File::class)),
            'possibleDuplicates' => array_map(static fn (PersonId $id): string => $id->value, Typed::objects($state['possibleDuplicates'], PersonId::class)),
            'notDuplicates' => array_map(static fn (PersonId $id): string => $id->value, Typed::objects($state['notDuplicates'], PersonId::class)),
            'origin' => Typed::object($state['origin'], Origin::class)->value,
            'createdAt' => Documents::moment(Typed::object($state['createdAt'], \DateTimeImmutable::class)),
            'modifiedAt' => Documents::moment(Typed::object($state['modifiedAt'], \DateTimeImmutable::class)),
        ];
    }

    /**
     * Restores the person from its item's untyped document.
     */
    public static function person(mixed $document): Person
    {
        $document = Typed::document($document);
        $name = Typed::document($document['name']);

        return Hydrator::restore(Person::class, [
            'id' => new PersonId(Typed::string($document['id'])),
            'accountId' => new AccountId(Typed::string($document['accountId'])),
            'purpose' => Purpose::from(Typed::string($document['purpose'])),
            'name' => new PersonName(Typed::stringOrNull($name['firstName'] ?? null), Typed::stringOrNull($name['lastName'] ?? null)),
            'contactDetails' => Documents::contactDetailsOf($document['contactDetails']),
            'references' => self::referencesOf($document),
            'formerReferences' => self::formerReferencesOf($document)->all(),
            'jobTitle' => Typed::stringOrNull($document['jobTitle']),
            'civility' => self::civilityOf($document),
            'picture' => Documents::fileOf($document['picture']),
            'possibleDuplicates' => array_map(static fn (string $id): PersonId => new PersonId($id), Typed::strings($document['possibleDuplicates'] ?? [])),
            'notDuplicates' => array_map(static fn (string $id): PersonId => new PersonId($id), Typed::strings($document['notDuplicates'] ?? [])),
            'origin' => Documents::originOf($document),
            'createdAt' => Documents::momentOf(Typed::string($document['createdAt'])),
            'modifiedAt' => Documents::momentOf(Typed::string($document['modifiedAt'])),
        ]);
    }

    /**
     * A person saved before titles were logged has an unknown title.
     *
     * @param array<string, mixed> $document
     */
    public static function civilityOf(array $document): Civility
    {
        return Civility::tryFrom(Typed::stringOrNull($document['title'] ?? null) ?? '') ?? Civility::X;
    }

    /**
     * Returns their links as they were logged. A person saved before links existed has their LinkedIn profile as
     * their only link (ADR 0139). A person saved before LinkedIn profiles were logged has none.
     *
     * @param array<string, mixed> $document
     */
    public static function referencesOf(array $document): ExternalReferences
    {
        return isset($document['references']) ? Documents::referencesOf($document['references']) : Documents::referencesBeforeTheLinks(array_filter([Typed::stringOrNull($document['linkedInProfile'] ?? null)]), Designated::Person);
    }

    /**
     * Whether they are reached on LinkedIn, through a LinkedIn profile among their links.
     *
     * @param array<string, mixed> $document
     */
    public static function isOnLinkedIn(array $document): bool
    {
        return self::referencesOf($document)->hasOneOf(KnownSource::LinkedInProfile);
    }

    /**
     * Returns the key that recognizes each link they hold, current or former.
     *
     * @param array<string, mixed> $document
     *
     * @return list<string>
     */
    public static function referenceKeysHeld(array $document): array
    {
        return array_map(static fn (ExternalReference $reference): string => $reference->key(), self::person($document)->referencesHeld());
    }

    /**
     * Returns the document with their current and former links recognized again from their addresses.
     * `app:keys:rebuild` writes it once a new source is known.
     *
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    public static function recognizedAgain(array $document): array
    {
        return [
            'references' => Documents::referencesRecognizedAgain(Documents::references(self::referencesOf($document)), Designated::Person),
            'formerReferences' => Documents::referencesRecognizedAgain(Documents::references(self::formerReferencesOf($document)), Designated::Person),
        ] + $document;
    }

    /**
     * Returns their former links. A person saved before links existed has their former LinkedIn profiles instead
     * (ADR 0159).
     *
     * @param array<string, mixed> $document
     */
    private static function formerReferencesOf(array $document): ExternalReferences
    {
        return isset($document['formerReferences']) ? Documents::referencesOf($document['formerReferences']) : Documents::referencesBeforeTheLinks(Typed::strings($document['formerLinkedInProfiles'] ?? []), Designated::Person);
    }
}
