<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Policy;

use Prospecter\Core\Booking\Domain\Model\Availability;
use Prospecter\Core\Booking\Domain\Model\BookableDay;
use Prospecter\Core\Booking\Domain\Model\BusyPeriod;
use Prospecter\Core\Booking\Domain\Model\Weekday;
use Prospecter\Core\Booking\Domain\Model\Window;

/**
 * The slots a booker can choose (ADR 0153). The host's windows are cut into 15-minute slots, for 14 days from tomorrow
 * on the host's clock. No slot falls on an inactive period's day or overlaps a time when the host is already busy.
 */
final readonly class FreeSlots
{
    private const int SLOT_MINUTES = 15;
    private const int HORIZON_DAYS = 14;

    /**
     * @param list<BusyPeriod> $busyPeriods
     *
     * @return list<BookableDay> the 14 days from tomorrow, each with its free slots, the soonest first
     */
    #[\NoDiscard]
    public static function given(Availability $availability, array $busyPeriods, \DateTimeImmutable $at): array
    {
        $today = $at->setTimezone(new \DateTimeZone($availability->timeZone->name))->setTime(0, 0);

        return array_map(static fn (int $ahead): BookableDay => self::dayOf($availability, $busyPeriods, $today->modify(sprintf('+%d days', $ahead))), range(1, self::HORIZON_DAYS));
    }

    /**
     * @param list<BusyPeriod> $busyPeriods
     */
    private static function dayOf(Availability $availability, array $busyPeriods, \DateTimeImmutable $day): BookableDay
    {
        $window = array_find($availability->windows->windows, static fn (Window $window): bool => $window->weekday === Weekday::from(strtolower($day->format('l'))));
        $inactive = $availability->isInactiveOn($day->format('Y-m-d'));

        return new BookableDay($day->format('Y-m-d'), null === $window || $inactive ? [] : self::freeSlotsOf($window, $busyPeriods, $day));
    }

    /**
     * @param list<BusyPeriod> $busyPeriods
     *
     * @return list<\DateTimeImmutable>
     */
    private static function freeSlotsOf(Window $window, array $busyPeriods, \DateTimeImmutable $day): array
    {
        $isFree = static fn (\DateTimeImmutable $slot): bool => !array_any($busyPeriods, static fn (BusyPeriod $busy): bool => $busy->overlaps($slot, self::endOf($slot)));

        return array_values(array_filter(self::slotsOf($window, $day), $isFree));
    }

    private static function endOf(\DateTimeImmutable $slot): \DateTimeImmutable
    {
        return $slot->modify(sprintf('+%d minutes', self::SLOT_MINUTES));
    }

    /**
     * @return list<\DateTimeImmutable>
     */
    private static function slotsOf(Window $window, \DateTimeImmutable $day): array
    {
        $slots = [];
        for ($minutes = $window->from->minutesOfTheDay; $minutes + self::SLOT_MINUTES <= $window->to->minutesOfTheDay; $minutes += self::SLOT_MINUTES) {
            $slots[] = $day->setTime(intdiv($minutes, 60), $minutes % 60);
        }

        return $slots;
    }
}
