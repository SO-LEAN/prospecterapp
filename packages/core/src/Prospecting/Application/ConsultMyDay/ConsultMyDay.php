<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultMyDay;

/**
 * The day of the prospector, from its start until the start of the next, at the offset of its reader, and what is to
 * come after it; what each exchange offers is told at the moment of the reading.
 */
final readonly class ConsultMyDay
{
    public function __construct(
        public string $prospectorId,
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $until,
        public \DateTimeImmutable $at,
    ) {
    }
}
