<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
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
}
