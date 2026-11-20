<?php

namespace App\PersonalData\Persistence;

use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;

/**
 * The keys of the items of the persons, written by `DynamoPersonRepository` and read by the retrievers
 * (ADR 0088): one format for both.
 */
final class PersonalDataKeys
{
    public const string PERSON = 'PERSON';
    public const string FIRST_NAME = 'FIRST_NAME';
    public const string EMAIL = 'EMAIL';
    public const string REFERENCE = 'REFERENCE';

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

    /**
     * The links to the contacts of an account resembling each other, by their names.
     */
    public static function ofResemblance(string $accountId, string $key): string
    {
        return sprintf('RESEMBLANCE#%s#%s', $accountId, $key);
    }

    /**
     * The email a person holds among those their account keeps for a purpose: the item `EMAIL` names its holder,
     * `ownerId`.
     */
    public static function email(AccountId $accountId, Purpose $purpose, string $email): string
    {
        return sprintf('%s#%s#%s#%s', self::EMAIL, $accountId->value, $purpose->value, $email);
    }

    /**
     * A link a contact holds in their account, as their own or a former one: the item `REFERENCE` names its holder,
     * `ownerId`.
     */
    public static function reference(AccountId $accountId, ExternalReference $reference): string
    {
        return sprintf('%s#%s#%s', self::REFERENCE, $accountId->value, $reference->key());
    }
}
