<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Event;

/**
 * The host set their weekly windows in their time zone.
 */
final readonly class WindowsSet extends BookingPageEvent
{
}
