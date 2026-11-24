<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda\ConsultCalendar;

/**
 * Reads the prospector's calendar over a period of six weeks at most. The period runs from the start of its first
 * day until the start of the day after its last day. The query tells what each exchange offers and what is late at
 * the reading moment, which the reader gives on their clock.
 */
final readonly class ConsultCalendar
{
    public function __construct(
        public string $prospectorId,
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $until,
        public \DateTimeImmutable $at,
    ) {
    }
}
