<?php

namespace App\Booking\Persistence;

/**
 * The keys of the items of the table of Booking, written by the repository and read by the retrievers (ADR 0088,
 * 0151): the page of a host in its own partition, and the link of a code to its page, found by the code alone.
 */
final class BookingKeys
{
    public const string PAGE = 'BOOKING_PAGE';
    public const string CODE = 'BOOKING_CODE';

    public static function page(string $host): string
    {
        return self::PAGE.'#'.$host;
    }

    public static function code(string $code): string
    {
        return self::CODE.'#'.$code;
    }
}
