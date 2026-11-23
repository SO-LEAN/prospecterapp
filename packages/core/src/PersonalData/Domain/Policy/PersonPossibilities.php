<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Policy;

use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonIntent;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * What a reader may do with a person (ADR 0088). A person changes their own data. A member with the right changes the
 * persons their account prospects, but not a colleague's profile. Only a prospected person can be forgotten or have a
 * title. The person refuses the rest, and the person's reading tells what is possible.
 */
final class PersonPossibilities
{
    /**
     * @return list<PersonIntent>
     */
    #[\NoDiscard]
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
    #[\NoDiscard]
    public static function allows(PersonIntent $intent, Reader $reader, PersonId $person, Purpose $purpose): bool
    {
        $isAContactManagedByTheReader = Purpose::Prospecting === $purpose && $reader->readsPersonalData;

        return $intent->concernsAContactOnly() ? $isAContactManagedByTheReader : $person->equals($reader->personId) || $isAContactManagedByTheReader;
    }
}
