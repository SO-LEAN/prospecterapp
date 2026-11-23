<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Event;

/**
 * The host opened their booking page. Its public link offers the host's slots.
 */
final readonly class BookingOpened extends BookingPageEvent
{
}
