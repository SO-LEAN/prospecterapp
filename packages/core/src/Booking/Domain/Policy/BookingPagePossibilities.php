<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Policy;

use Prospecter\Core\Booking\Domain\Model\BookingPageIntent;

/**
 * What a host can do with a booking page in its state. A closed page opens. An open page closes. A page with a link can
 * change its link.
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
