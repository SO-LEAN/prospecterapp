<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Event;

/**
 * The host closed their booking page: its public link offers nothing, its settings kept.
 */
final readonly class BookingClosed extends BookingPageEvent
{
}
