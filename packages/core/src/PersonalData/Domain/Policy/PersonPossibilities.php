<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Policy;

use Prospecter\Core\PersonalData\Domain\Model\PersonIntent;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * What a reader may do of a person (ADR 0088): a person changes their own data; a member with the right
 * changes the persons their account prospects, not the profile of a colleague, and only a prospected person is
 * forgotten. The person refuses the rest, the reading of a person tells it.
 */
final class PersonPossibilities
{
    /**
     * @return list<PersonIntent>
     */
    public static function given(Reader $reader, PersonId $person, AccountId $accountOfThePerson, Purpose $purpose): array
    {
        if (!PersonReading::isReachableBy($reader, $accountOfThePerson)) {
            return [];
        }

        return array_values(array_filter(PersonIntent::cases(), static fn (PersonIntent $intent): bool => self::allows($intent, $reader, $person, $purpose)));
    }

    /**
     * For a person the reader reaches.
     */
    public static function allows(PersonIntent $intent, Reader $reader, PersonId $person, Purpose $purpose): bool
    {
        $isAContactManagedByTheReader = Purpose::Prospecting === $purpose && $reader->readsPersonalData;

        return PersonIntent::Forget === $intent ? $isAContactManagedByTheReader : $person->equals($reader->personId) || $isAContactManagedByTheReader;
    }
}
