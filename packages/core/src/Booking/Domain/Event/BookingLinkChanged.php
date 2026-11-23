<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Event;

/**
 * The host drew a new code for their public link. The former link leads nowhere.
 */
final readonly class BookingLinkChanged extends BookingPageEvent
{
}
