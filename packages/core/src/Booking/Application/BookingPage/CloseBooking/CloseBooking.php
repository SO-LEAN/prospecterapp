<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\BookingPage\CloseBooking;

final readonly class CloseBooking
{
    public function __construct(
        public string $memberId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
