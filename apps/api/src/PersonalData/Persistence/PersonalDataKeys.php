<?php

declare(strict_types=1);

namespace App\PersonalData\Persistence;

use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;

/**
 * The keys of the persons' items. `DynamoPersonRepository` writes them and the retrievers read them (ADR 0088).
 * Both use this one format.
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
     * The scope of a person's search: the persons their account keeps for the same purpose.
     */
    public static function scopeOf(AccountId $accountId, Purpose $purpose): string
    {
        return $accountId->value.'#'.$purpose->value;
    }

    /**
     * An account's contacts, the persons it keeps for prospecting, listed in `GSI1`.
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
     * The links between an account's contacts whose names resemble each other.
     */
    public static function ofResemblance(string $accountId, string $key): string
    {
        return sprintf('RESEMBLANCE#%s#%s', $accountId, $key);
    }

    /**
     * The email a person holds among the persons their account keeps for a purpose. The `EMAIL` item names its
     * holder in `ownerId`.
     */
    public static function email(AccountId $accountId, Purpose $purpose, string $email): string
    {
        return sprintf('%s#%s#%s#%s', self::EMAIL, $accountId->value, $purpose->value, $email);
    }

    /**
     * A link a contact holds in their account, current or former. The `REFERENCE` item names its holder in
     * `ownerId`.
     */
    public static function reference(AccountId $accountId, ExternalReference $reference): string
    {
        return sprintf('%s#%s#%s', self::REFERENCE, $accountId->value, $reference->key());
    }
}
