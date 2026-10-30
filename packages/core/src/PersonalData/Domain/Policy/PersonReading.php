<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Policy;

use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Model\Reading;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Who reads a person, and how (ADR 0031): another account does not even learn that the person exists; the
 * person themself and a member of the account with the right to read personal data read them in clear, the
 * other members masked. Asked by the person and by its reading (ADR 0088).
 */
final class PersonReading
{
    public static function isReachableBy(Reader $reader, AccountId $accountOfThePerson): bool
    {
        return $accountOfThePerson->equals($reader->accountId);
    }

    public static function by(Reader $reader, PersonId $person): Reading
    {
        return $person->equals($reader->personId) || $reader->readsPersonalData ? Reading::Clear : Reading::Masked;
    }
}
