<?php

declare(strict_types=1);

namespace App\Booking;

/**
 * Draws the code of a public link (ADR 0148, 0151): 22 characters of base 62, each drawn by the cryptographic
 * generator of PHP, some 131 bits, nothing of the host in it.
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
