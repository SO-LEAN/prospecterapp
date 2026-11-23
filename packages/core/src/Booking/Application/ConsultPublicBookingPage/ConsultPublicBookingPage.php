<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ConsultPublicBookingPage;

/**
 * The booking page that a public link leads to, at a given moment. Anyone with the link can read it without signing in.
 * Its free slots start on the next day.
 */
final readonly class ConsultPublicBookingPage
{
    public function __construct(
        public string $code,
        public \DateTimeImmutable $at,
    ) {
    }
}
