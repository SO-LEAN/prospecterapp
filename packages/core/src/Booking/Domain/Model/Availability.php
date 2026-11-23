<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * When a host is bookable: their weekly windows on their clock, except on the days of their inactive periods.
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
     * @param string $day YYYY-MM-DD, on the host's clock
     */
    public function isInactiveOn(string $day): bool
    {
        return array_any($this->inactivePeriods, static fn (InactivePeriod $period): bool => $period->covers($day));
    }
}
