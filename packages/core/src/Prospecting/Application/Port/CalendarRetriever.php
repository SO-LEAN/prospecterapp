<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * Reads the calendar of a prospector, without restoring the exchanges (ADR 0088), each as they may read it
 * (`ExchangeReading`).
 */
interface CalendarRetriever
{
    /**
     * The exchanges the prospector holds, planned by them or noted by them, whatever their status, happening in the
     * period, by their moment then their id.
     *
     * @return list<ExchangeInTimeline>
     */
    public function heldBy(Prospector $holder, CalendarPeriod $period): array;
}
