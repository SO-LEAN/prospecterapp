<?php

namespace App\Accounts\Persistence;

/**
 * The keys of the items of the table of Accounts, written by the repositories and read by the retrievers
 * (ADR 0088): one format for both.
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
