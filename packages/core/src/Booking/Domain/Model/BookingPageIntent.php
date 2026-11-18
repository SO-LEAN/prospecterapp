<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * What a host may do with their booking page, as its state allows.
 */
enum BookingPageIntent
{
    /** Open the page to the bookers, its link drawn the first time. */
    case OpenBooking;
    /** Close it at once, its settings kept. */
    case CloseBooking;
    /** Draw another code for its link, the former one leading nowhere. */
    case ChangeBookingLink;
}
