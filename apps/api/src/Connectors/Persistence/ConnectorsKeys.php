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

    public static function authorization(string $state): string
    {
        return self::AUTHORIZATION.'#'.$state;
    }
}
