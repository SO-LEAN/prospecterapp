<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Event;

/**
 * The host drew another code for their public link: the former one leads nowhere.
 */
final readonly class BookingLinkChanged extends BookingPageEvent
{
}
