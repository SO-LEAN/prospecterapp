<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * An exchange the host still has planned, as Prospecting keeps it: its start, and its minutes when it has a duration.
 */
final readonly class PlannedExchange
{
    /**
     * An exchange without a duration takes one slot.
     */
    private const int MINUTES_WITHOUT_A_DURATION = 15;

    public function __construct(
        public \DateTimeImmutable $start,
        public ?int $minutes,
    ) {
    }

    public function busyPeriod(): BusyPeriod
    {
        return new BusyPeriod($this->start, $this->minutes ?? self::MINUTES_WITHOUT_A_DURATION);
    }
}
