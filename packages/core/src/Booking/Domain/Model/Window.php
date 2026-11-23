<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Exception\WindowOffTheQuarterHour;
use Prospecter\Core\Booking\Domain\Exception\WindowTooShort;

/**
 * When the host is bookable on a weekday: from one time to a later one. Both times fall on a quarter hour. The window
 * holds at least one 15-minute slot.
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
