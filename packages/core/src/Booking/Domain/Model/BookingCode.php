<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Exception\InvalidBookingCode;

/**
 * The code in a booking page's public link: 22 random base-62 characters, at least 128 bits. The API draws it like an
 * id. It never derives it from the host. The core checks only its shape.
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
