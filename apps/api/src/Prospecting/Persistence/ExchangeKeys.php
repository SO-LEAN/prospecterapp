<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Typed;

/**
 * The keys of the exchanges' items. `DynamoExchangeRepository` writes them and the retrievers read them (ADR 0088),
 * so both share one format.
 */
final class ExchangeKeys
{
    public const string EXCHANGE = 'EXCHANGE';

    public const string FINGERPRINT = 'FINGERPRINT';

    /**
     * The exchange that an account logged under a fingerprint. The key stays once the exchange is erased (ADR 0166).
     */
    public static function fingerprint(string $accountId, string $fingerprint): string
    {
        return sprintf('%s#%s#%s', self::FINGERPRINT, $accountId, $fingerprint);
    }

    /**
     * The fingerprints of an account's exchanges with a contact, in `GSI1`.
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
     * An account's exchanges, in `GSI2`.
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
     * The exchanges a member planned: their links' PK, and their agenda's partition in `GSI1`.
     */
    public static function agenda(string $member): string
    {
        return 'AGENDA#'.$member;
    }

    /**
     * The exchanges a member holds, whatever their status: their links' PK, and their calendar's partition in
     * `GSI1`.
     */
    public static function calendar(string $member): string
    {
        return 'CALENDAR#'.$member;
    }

    /**
     * An exchange's position in a calendar: the moment it happens, cancelled or not, then its id.
     *
     * @param array<string, mixed> $document
     */
    public static function calendarPositionOf(array $document): string
    {
        return Typed::string(Typed::document($document['facts'])['occurredAt']).'#'.Typed::string($document['id']);
    }

    /**
     * A moment's position in a timeline, after every exchange that happens at that moment. It is the bound between the
     * past and what is to come.
     */
    public static function positionAfter(\DateTimeImmutable $moment): string
    {
        return Documents::moment($moment).'#~';
    }

    /**
     * A moment's position in a timeline, before every exchange that happens at that moment.
     */
    public static function positionBefore(\DateTimeImmutable $moment): string
    {
        return Documents::moment($moment).'#';
    }

    /**
     * An exchange's position in a timeline, which is also a slice's cursor. It is the moment the exchange is listed at,
     * then its id. That moment is when the exchange happened, except for a planned exchange cancelled before then
     * (`ExchangeChronology`).
     *
     * @param array<string, mixed> $document
     */
    public static function positionOf(array $document): string
    {
        return Documents::moment(ExchangeDocument::listedAt($document)).'#'.Typed::string($document['id']);
    }
}
