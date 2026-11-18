<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * When a host may be booked: the windows of their week on their clock, but on the days of their inactive periods.
 */
final readonly class Availability
{
    /**
     * @param list<InactivePeriod> $inactivePeriods
     */
    public function __construct(
        public WeeklyWindows $windows,
        public HostTimeZone $timeZone,
        public array $inactivePeriods,
    ) {
    }

    /**
     * @param string $day YYYY-MM-DD, on the clock of the host
     */
    public function isInactiveOn(string $day): bool
    {
        return array_any($this->inactivePeriods, static fn (InactivePeriod $period): bool => $period->covers($day));
    }
}
