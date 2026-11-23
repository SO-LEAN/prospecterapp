<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ConsultMyBookingPage;

/**
 * The booking page of the member who reads it, on their clock. The inactive periods that ended before the reading's day
 * are left out.
 */
final readonly class ConsultMyBookingPage
{
    /**
     * @param string $timeZone the IANA name of the reader's clock
     */
    public function __construct(
        public string $memberId,
        public string $timeZone,
        public \DateTimeImmutable $at,
    ) {
    }
}
