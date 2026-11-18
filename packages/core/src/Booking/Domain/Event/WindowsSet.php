<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Event;

/**
 * The host set the windows of their week, in their time zone.
 */
final readonly class WindowsSet extends BookingPageEvent
{
}
