<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultCalendar;

/**
 * The calendar of the prospector over a period: from the start of its first day, until the start of the day after
 * its last, six weeks at most.
 */
final readonly class ConsultCalendar
{
    public function __construct(
        public string $prospectorId,
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $until,
    ) {
    }
}
