<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Policy;

use Prospecter\Core\Booking\Domain\Model\BookingPageIntent;

/**
 * What a booking page offers in its state: closed, it opens; open, it closes; once it has a link, its link changes.
 */
final class BookingPagePossibilities
{
    /**
     * @return list<BookingPageIntent>
     */
    #[\NoDiscard]
    public static function given(bool $open, bool $hasALink): array
    {
        return [
            $open ? BookingPageIntent::CloseBooking : BookingPageIntent::OpenBooking,
            ...($hasALink ? [BookingPageIntent::ChangeBookingLink] : []),
        ];
    }
}
