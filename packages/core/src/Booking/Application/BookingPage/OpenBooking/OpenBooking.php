<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\BookingPage\OpenBooking;

final readonly class OpenBooking
{
    /**
     * @param string $drawnCode 22 random base-62 characters that the API drew. They become the link's code when the page has no link yet
     */
    public function __construct(
        public string $memberId,
        public string $drawnCode,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
