<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;

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

    /**
     * The exchanges of an account, in `GSI2`.
     */
    public static function ofAccount(string $accountId): string
    {
        return 'ACCOUNT_EXCHANGES#'.$accountId;
    }

    public static function toMakeConform(string $organization): string
    {
        return 'TO_MAKE_CONFORM#'.$organization;
    }

    /**
     * The exchanges a member planned: the PK of their links, and the partition of their agenda in `GSI1`.
     */
    public static function agenda(string $member): string
    {
        return 'AGENDA#'.$member;
    }

    /**
     * Where a moment stands in a timeline, after every exchange happening then: the bound between the past and
     * what is to come.
     */
    public static function positionAfter(\DateTimeImmutable $moment): string
    {
        return Documents::moment($moment).'#~';
    }

    /**
     * Where a moment stands in a timeline, before every exchange happening then.
     */
    public static function positionBefore(\DateTimeImmutable $moment): string
    {
        return Documents::moment($moment).'#';
    }

    /**
     * Where an exchange stands in a timeline, and the cursor of a slice: the moment it is listed at, the moment it
     * happened but for a planned exchange cancelled before it (`ExchangeChronology`), then its id.
     *
     * @param array<string, mixed> $document
     */
    public static function positionOf(array $document): string
    {
        return Documents::moment(ExchangeDocument::listedAt($document)).'#'.$document['id'];
    }
}
