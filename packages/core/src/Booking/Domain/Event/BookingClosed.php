<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Event;

/**
 * The host closed their booking page. Its public link offers nothing. The page keeps its settings.
 */
final readonly class BookingClosed extends BookingPageEvent
{
}
