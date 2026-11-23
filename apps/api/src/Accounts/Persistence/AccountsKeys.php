<?php

declare(strict_types=1);

namespace App\Accounts\Persistence;

/**
 * The keys of the Accounts table's items. The repositories write them and the retrievers read them (ADR 0088), so
 * both use one format.
 */
final class AccountsKeys
{
    public const string ACCOUNT = 'ACCOUNT';
    public const string MEMBER = 'MEMBER';
    /** every account, listed in `GSI1` by its id */
    public const string ALL_ACCOUNTS = 'ACCOUNTS';

    public static function account(string $id): string
    {
        return self::ACCOUNT.'#'.$id;
    }

    public static function member(string $id): string
    {
        return self::MEMBER.'#'.$id;
    }
}
