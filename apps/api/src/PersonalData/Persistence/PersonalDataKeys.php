<?php

namespace App\PersonalData\Persistence;

use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The keys of the items of the persons, written by `DynamoPersonRepository` and read by the retrievers
 * (ADR 0088): one format for both.
 */
final class PersonalDataKeys
{
    public const string PERSON = 'PERSON';

    public static function person(string $id): string
    {
        return self::PERSON.'#'.$id;
    }

    /**
     * What a person is searched among: the persons their account keeps for the same purpose.
     */
    public static function scopeOf(AccountId $accountId, Purpose $purpose): string
    {
        return $accountId->value.'#'.$purpose->value;
    }

    /**
     * The contacts of an account, the persons it keeps for its prospecting, listed in `GSI1`.
     */
    public static function contactsOf(string $accountId): string
    {
        return self::listOf(new AccountId($accountId), Purpose::Prospecting);
    }

    /**
     * The persons an account keeps for a purpose, listed in `GSI1`.
     */
    public static function listOf(AccountId $accountId, Purpose $purpose): string
    {
        return sprintf('ACCOUNT#%s#%s', $accountId->value, $purpose->value);
    }
}
