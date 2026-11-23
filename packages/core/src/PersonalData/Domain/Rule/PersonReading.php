<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Rule;

use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Model\Reading;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Who reads a person, and how (ADR 0031). Another account does not even learn that the person exists. The person
 * themself, and a member of the account with the right to read personal data, read them unmasked. The other members
 * read them masked. The person and their reading both ask this rule (ADR 0088).
 */
final class PersonReading
{
    #[\NoDiscard]
    public static function isReachableBy(Reader $reader, AccountId $accountOfThePerson): bool
    {
        return $accountOfThePerson->equals($reader->accountId);
    }

    #[\NoDiscard]
    public static function by(Reader $reader, PersonId $person): Reading
    {
        return $person->equals($reader->personId) || $reader->readsPersonalData ? Reading::Clear : Reading::Masked;
    }

    /**
     * Every member reads a contact's name unmasked, to know who they have an exchange with. A member's profile is read
     * like the rest of the person (ADR 0129).
     */
    #[\NoDiscard]
    public static function ofTheName(Reader $reader, PersonId $person, Purpose $purpose): Reading
    {
        return Purpose::Prospecting === $purpose ? Reading::Clear : self::by($reader, $person);
    }

    /**
     * Only a member with the right to manage personal data reads which contact a contact is suspected of duplicating.
     * Only that member says, after checking, that the contact is not a duplicate.
     */
    #[\NoDiscard]
    public static function showsPossibleDuplicatesTo(Reader $reader): bool
    {
        return $reader->readsPersonalData;
    }
}
