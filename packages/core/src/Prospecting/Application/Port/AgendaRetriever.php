<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * Reads the exchanges a prospector planned without restoring them (ADR 0088). Each exchange reads as the prospector
 * may read it (`ExchangeReading`).
 */
interface AgendaRetriever
{
    /**
     * Returns the exchanges the prospector planned that start after `from` and before `until`, soonest first.
     *
     * @return list<ExchangeInTimeline>
     */
    public function plannedBy(Prospector $holder, \DateTimeImmutable $from, \DateTimeImmutable $until): array;

    /**
     * Returns the exchanges the prospector planned that happen in the period, soonest first. The period's start is
     * included.
     *
     * @return list<ExchangeInTimeline>
     */
    public function plannedIn(Prospector $holder, CalendarPeriod $period): array;

    /**
     * Returns the first `count` exchanges the prospector planned that happen at `from` or later, soonest first.
     *
     * @return list<ExchangeInTimeline>
     */
    public function firstPlannedFrom(Prospector $holder, \DateTimeImmutable $from, int $count): array;

    /**
     * Returns the exchanges the prospector planned that start before `until`, oldest first.
     *
     * @return list<ExchangeInTimeline>
     */
    public function plannedBefore(Prospector $holder, \DateTimeImmutable $until): array;
}
