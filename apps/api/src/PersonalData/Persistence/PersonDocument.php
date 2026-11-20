<?php

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use Prospecter\Core\PersonalData\Domain\Model\Civility;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\KnownSource;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A person as a plain document of its DynamoDB item, and back (ADR 0048): its value objects as they are,
 * its recorded events never.
 */
final class PersonDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function of(Person $person): array
    {
        $state = Hydrator::stateOf($person);

        return [
            'id' => (string) $state['id'],
            'accountId' => (string) $state['accountId'],
            'purpose' => $state['purpose']->value,
            'name' => ['firstName' => $state['name']->firstName, 'lastName' => $state['name']->lastName],
            'contactDetails' => Documents::contactDetails($state['contactDetails']),
            'references' => Documents::references($state['references']),
            'formerReferences' => Documents::references(ExternalReferences::of($state['formerReferences'])),
            'jobTitle' => $state['jobTitle'],
            'civility' => $state['civility']->value,
            'picture' => Documents::file($state['picture']),
            'createdAt' => Documents::moment($state['createdAt']),
            'modifiedAt' => Documents::moment($state['modifiedAt']),
        ];
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function person(array $document): Person
    {
        return Hydrator::restore(Person::class, [
            'id' => new PersonId($document['id']),
            'accountId' => new AccountId($document['accountId']),
            'purpose' => Purpose::from($document['purpose']),
            'name' => new PersonName($document['name']['firstName'] ?? null, $document['name']['lastName'] ?? null),
            'contactDetails' => Documents::contactDetailsOf($document['contactDetails']),
            'references' => self::referencesOf($document),
            'formerReferences' => self::formerReferencesOf($document)->all(),
            'jobTitle' => $document['jobTitle'],
            'civility' => self::civilityOf($document),
            'picture' => Documents::fileOf($document['picture']),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }

    /**
     * A person saved before the civilities were noted has an unknown one.
     *
     * @param array<string, mixed> $document
     */
    public static function civilityOf(array $document): Civility
    {
        return Civility::tryFrom($document['civility'] ?? '') ?? Civility::X;
    }

    /**
     * Their links, recognized again by their addresses; their LinkedIn profile for a person saved before the links were
     * (ADR 0139), none before the profiles were noted.
     *
     * @param array<string, mixed> $document
     */
    public static function referencesOf(array $document): ExternalReferences
    {
        return Documents::referencesOf(isset($document['references']) ? array_column($document['references'], 'address') : array_filter([$document['linkedInProfile'] ?? null]), Designated::Person);
    }

    /**
     * Whether they are reached on LinkedIn: a profile there among their links.
     *
     * @param array<string, mixed> $document
     */
    public static function isOnLinkedIn(array $document): bool
    {
        return self::referencesOf($document)->hasOneOf(KnownSource::LinkedInProfile);
    }

    /**
     * What recognizes each link they hold, theirs and their former ones.
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
     * Their former links, their former LinkedIn profiles for a person saved before the links were (ADR 0159).
     *
     * @param array<string, mixed> $document
     */
    private static function formerReferencesOf(array $document): ExternalReferences
    {
        return Documents::referencesOf(isset($document['formerReferences']) ? array_column($document['formerReferences'], 'address') : $document['formerLinkedInProfiles'] ?? [], Designated::Person);
    }
}
