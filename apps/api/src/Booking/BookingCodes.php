<?php

declare(strict_types=1);

namespace App\Booking;

/**
 * Draws a public link's code (ADR 0148, 0151): 22 base-62 characters, each drawn by PHP's cryptographic generator. That
 * gives about 131 bits, with nothing from the host in it.
 */
final readonly class BookingCodes
{
    private const string BASE62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    private const int LENGTH = 22;

    public function draw(): string
    {
        return implode('', array_map(static fn (): string => self::BASE62[random_int(0, strlen(self::BASE62) - 1)], range(1, self::LENGTH)));
    }
}
