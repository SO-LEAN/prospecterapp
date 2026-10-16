<?php

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonName;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
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
            'jobTitle' => $state['jobTitle'],
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
            'jobTitle' => $document['jobTitle'],
            'picture' => Documents::fileOf($document['picture']),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }
}
