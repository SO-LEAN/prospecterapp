<?php

namespace App\Booking\Persistence;

/**
 * The keys of the items of the table of Booking, written by the repository and read by the retrievers (ADR 0088,
 * 0151): the page of a host in its own partition, and the link of a code to its page, found by the code alone; the
 * counts of a limiter of the public routes, by what it limits (ADR 0153); an appointment by its identity, and the
 * slot it holds in the partition of the slots of its host, by its start (ADR 0164).
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
