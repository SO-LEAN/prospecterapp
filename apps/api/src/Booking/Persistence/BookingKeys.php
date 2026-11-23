<?php

declare(strict_types=1);

namespace App\Booking\Persistence;

/**
 * The keys of the Booking table's items. The repository writes them and the retrievers read them (ADR 0088, 0151):
 *
 * - a host's page, in its own partition;
 * - a code's link to its page, found by the code alone;
 * - a public route limiter's counts, by what the limiter limits (ADR 0153);
 * - an appointment, by its id (ADR 0164);
 * - the slot an appointment holds, in the partition of its host's slots, by the slot's start.
 */
final class BookingKeys
{
    public const string PAGE = 'BOOKING_PAGE';
    public const string CODE = 'BOOKING_CODE';
    public const string RATE_LIMIT = 'RATE_LIMIT';
    public const string APPOINTMENT = 'APPOINTMENT';
    public const string SLOT = 'SLOT';

    public static function page(string $host): string
    {
        return self::PAGE.'#'.$host;
    }

    public static function code(string $code): string
    {
        return self::CODE.'#'.$code;
    }

    public static function appointment(string $id): string
    {
        return self::APPOINTMENT.'#'.$id;
    }

    public static function slotsOf(string $host): string
    {
        return self::SLOT.'S#'.$host;
    }

    public static function slot(string $start): string
    {
        return self::SLOT.'#'.$start;
    }

    public static function rateLimit(string $limited): string
    {
        return self::RATE_LIMIT.'#'.$limited;
    }
}
