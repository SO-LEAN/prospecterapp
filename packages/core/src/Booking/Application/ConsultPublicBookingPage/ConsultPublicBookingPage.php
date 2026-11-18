<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ConsultPublicBookingPage;

/**
 * The booking page a public link leads to, read by anyone holding it, without signing in, at a moment: its free slots
 * start the day after.
 */
final readonly class ConsultPublicBookingPage
{
    public function __construct(
        public string $code,
        public \DateTimeImmutable $at,
    ) {
    }
}
