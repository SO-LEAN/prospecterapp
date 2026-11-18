<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Exception\WindowOffTheQuarterHour;
use Prospecter\Core\Booking\Domain\Exception\WindowTooShort;

/**
 * When the host may be booked on a day of the week: from a time to a later one, both on the quarter hour, long
 * enough for one slot of 15 minutes at least.
 */
final readonly class Window
{
    private const int SHORTEST_SLOT_MINUTES = 15;

    public function __construct(
        public Weekday $weekday,
        public WallClockTime $from,
        public WallClockTime $to,
    ) {
        if ($from->minutesUntil($to) < self::SHORTEST_SLOT_MINUTES) {
            throw WindowTooShort::holdsNoSlot();
        }
        if (!$from->isOnTheQuarterHour() || !$to->isOnTheQuarterHour()) {
            throw WindowOffTheQuarterHour::refused();
        }
    }
}
