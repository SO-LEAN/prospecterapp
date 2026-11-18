<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Event;

/**
 * The host opened their booking page: its public link offers their slots.
 */
final readonly class BookingOpened extends BookingPageEvent
{
}
