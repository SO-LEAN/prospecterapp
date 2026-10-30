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

    public static function account(string $id): string
    {
        return self::ACCOUNT.'#'.$id;
    }

    public static function member(string $id): string
    {
        return self::MEMBER.'#'.$id;
    }
}
