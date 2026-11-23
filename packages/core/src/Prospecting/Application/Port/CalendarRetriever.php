<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * Reads a prospector's calendar without restoring the exchanges (ADR 0088). Each exchange reads as the prospector may
 * read it (`ExchangeReading`).
 */
interface CalendarRetriever
{
    /**
     * Returns the exchanges the prospector holds that happen in the period, whatever their status. The prospector
     * planned or logged each of them. They are sorted by their moment, then by their id.
     *
     * @return list<ExchangeInTimeline>
     */
    public function heldBy(Prospector $holder, CalendarPeriod $period): array;
}
