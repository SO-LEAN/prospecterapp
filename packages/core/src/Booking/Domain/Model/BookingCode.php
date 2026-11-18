<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Exception\InvalidBookingCode;

/**
 * The code of the public link of a booking page: 128 random bits written in base 62, drawn by the client like an
 * identity, never derived from the host; the core checks its shape only.
 */
final readonly class BookingCode
{
    private const string BASE62_OF_128_BITS = '/^[0-9A-Za-z]{22}$/';

    public function __construct(public string $value)
    {
        if (1 !== preg_match(self::BASE62_OF_128_BITS, $value)) {
            throw InvalidBookingCode::refused();
        }
    }
}
