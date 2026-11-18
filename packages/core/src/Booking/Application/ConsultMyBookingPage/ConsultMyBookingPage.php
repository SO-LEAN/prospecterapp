<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ConsultMyBookingPage;

/**
 * The booking page of the member who reads it, on their clock: the inactive periods ended before the day of the
 * reading are no longer read.
 */
final readonly class ConsultMyBookingPage
{
    /**
     * @param string $timeZone the IANA name of the clock of the reader
     */
    public function __construct(
        public string $memberId,
        public string $timeZone,
        public \DateTimeImmutable $at,
    ) {
    }
}
