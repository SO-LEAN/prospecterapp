<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Exception\InvalidBookingCode;

/**
 * The code of the public link of a booking page: 22 random characters of base 62, 128 bits at least, drawn by the API
 * like an identity, never derived from the host; the core checks its shape only.
 */
final readonly class BookingCode
{
    private const string TWENTY_TWO_OF_BASE62 = '/^[0-9A-Za-z]{22}$/';

    public function __construct(public string $value)
    {
        if (1 !== preg_match(self::TWENTY_TWO_OF_BASE62, $value)) {
            throw InvalidBookingCode::refused();
        }
    }
}
