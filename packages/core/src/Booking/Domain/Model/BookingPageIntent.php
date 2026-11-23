<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * What a host can do with their booking page in its current state.
 */
enum BookingPageIntent
{
    /** Open the page to bookers. The first opening draws its link. */
    case OpenBooking;
    /** Close the page at once. It keeps its settings. */
    case CloseBooking;
    /** Draw a new code for the page's link. The former link leads nowhere. */
    case ChangeBookingLink;
}
