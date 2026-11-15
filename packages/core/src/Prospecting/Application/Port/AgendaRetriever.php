<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * Reads the exchanges a prospector planned, without restoring them (ADR 0088), each as they may read it
 * (`ExchangeReading`).
 */
interface AgendaRetriever
{
    /**
     * The exchanges the prospector planned starting after `from` and before `until`, the soonest first.
     *
     * @return list<ExchangeInTimeline>
     */
    public function plannedBy(Prospector $holder, \DateTimeImmutable $from, \DateTimeImmutable $until): array;

    /**
     * The exchanges the prospector planned happening in the period, its start included, the soonest first.
     *
     * @return list<ExchangeInTimeline>
     */
    public function plannedIn(Prospector $holder, CalendarPeriod $period): array;

    /**
     * The first `count` exchanges the prospector planned happening from `from` on, the soonest first.
     *
     * @return list<ExchangeInTimeline>
     */
    public function firstPlannedFrom(Prospector $holder, \DateTimeImmutable $from, int $count): array;

    /**
     * The exchanges the prospector planned starting before `until`, the oldest first.
     *
     * @return list<ExchangeInTimeline>
     */
    public function plannedBefore(Prospector $holder, \DateTimeImmutable $until): array;
}
