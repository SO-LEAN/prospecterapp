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

    public static function member(string $id): string
    {
        return self::MEMBER.'#'.$id;
    }

    public static function connection(string $provider): string
    {
        return self::CONNECTION.'#'.$provider;
    }

    public static function authorization(string $state): string
    {
        return self::AUTHORIZATION.'#'.$state;
    }
}
