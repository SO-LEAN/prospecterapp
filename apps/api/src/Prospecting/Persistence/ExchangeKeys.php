<?php

namespace App\Prospecting\Persistence;

/**
 * The keys of the items of the exchanges, written by `DynamoExchangeRepository` and read by the retrievers
 * (ADR 0088): one format for both.
 */
final class ExchangeKeys
{
    public const string EXCHANGE = 'EXCHANGE';

    public static function exchange(string $id): string
    {
        return self::EXCHANGE.'#'.$id;
    }

    public static function keptFor(string $organization): string
    {
        return 'EXCHANGES#'.$organization;
    }

    public static function timeline(string $accountId, string $contact): string
    {
        return sprintf('TIMELINE#%s#%s', $accountId, $contact);
    }

    public static function organizationTimeline(string $organization): string
    {
        return 'ORGANIZATION_TIMELINE#'.$organization;
    }

    public static function toMakeConform(string $organization): string
    {
        return 'TO_MAKE_CONFORM#'.$organization;
    }

    /**
     * Where an exchange stands in a timeline, and the cursor of a slice: the moment it happened, then its id.
     *
     * @param array<string, mixed> $document
     */
    public static function positionOf(array $document): string
    {
        return $document['facts']['occurredAt'].'#'.$document['id'];
    }
}
