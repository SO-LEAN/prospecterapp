<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Typed;

/**
 * The keys of the items of the exchanges, written by `DynamoExchangeRepository` and read by the retrievers
 * (ADR 0088): one format for both.
 */
final class ExchangeKeys
{
    public const string EXCHANGE = 'EXCHANGE';

    public const string FINGERPRINT = 'FINGERPRINT';

    /**
     * The exchange an account noted under a fingerprint, kept once it is erased (ADR 0166).
     */
    public static function fingerprint(string $accountId, string $fingerprint): string
    {
        return sprintf('%s#%s#%s', self::FINGERPRINT, $accountId, $fingerprint);
    }

    /**
     * The fingerprints of the exchanges of an account with a contact, in `GSI1`.
     */
    public static function fingerprintsOf(string $accountId, string $contact): string
    {
        return sprintf('FINGERPRINTS#%s#%s', $accountId, $contact);
    }

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

    public static function toRedact(string $organization): string
    {
        return 'TO_REDACT#'.$organization;
    }

    /**
     * The exchanges a member planned: the PK of their links, and the partition of their agenda in `GSI1`.
     */
    public static function agenda(string $member): string
    {
        return 'AGENDA#'.$member;
    }

    /**
     * The exchanges a member holds, whatever their status: the PK of their links, and the partition of their
     * calendar in `GSI1`.
     */
    public static function calendar(string $member): string
    {
        return 'CALENDAR#'.$member;
    }

    /**
     * Where an exchange stands in a calendar: the moment it happens, cancelled or not, then its id.
     *
     * @param array<string, mixed> $document
     */
    public static function calendarPositionOf(array $document): string
    {
        return Typed::string(Typed::document($document['facts'])['occurredAt']).'#'.Typed::string($document['id']);
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
        return Documents::moment(ExchangeDocument::listedAt($document)).'#'.Typed::string($document['id']);
    }
}
