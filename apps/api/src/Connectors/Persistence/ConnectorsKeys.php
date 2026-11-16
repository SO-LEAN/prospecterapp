<?php

namespace App\Connectors\Persistence;

/**
 * The keys of the items of the table of Connectors, written by the repositories and read by the retriever
 * (ADR 0088): one format for both.
 */
final class ConnectorsKeys
{
    public const string AUTHORIZATION = 'AUTHORIZATION';
    private const string MEMBER = 'MEMBER';
    private const string CONNECTION = 'CONNECTION';
    private const string ACCOUNT = 'ACCOUNT';
    private const string CLIENT = 'CLIENT';
    private const string EXCHANGE = 'EXCHANGE';
    private const string ENTRY = 'ENTRY';
    private const string CONTACT = 'CONTACT';
    private const string ENTRIES = 'ENTRIES';

    public static function member(string $id): string
    {
        return self::MEMBER.'#'.$id;
    }

    public static function connection(string $provider): string
    {
        return self::CONNECTION.'#'.$provider;
    }

    public static function account(string $id): string
    {
        return self::ACCOUNT.'#'.$id;
    }

    public static function client(string $provider): string
    {
        return self::CLIENT.'#'.$provider;
    }

    public static function exchange(string $id): string
    {
        return self::EXCHANGE.'#'.$id;
    }

    public static function entry(string $provider): string
    {
        return self::ENTRY.'#'.$provider;
    }

    public static function entryOf(string $exchange, string $provider): string
    {
        return self::ENTRY.'#'.$exchange.'#'.$provider;
    }

    /**
     * The entries of a member, in `GSI1`, by the moment their exchange starts (ADR 0133).
     */
    public static function entriesOf(string $member): string
    {
        return self::ENTRIES.'#'.$member;
    }

    public static function contact(string $person): string
    {
        return self::CONTACT.'#'.$person;
    }

    public static function authorization(string $state): string
    {
        return self::AUTHORIZATION.'#'.$state;
    }
}
