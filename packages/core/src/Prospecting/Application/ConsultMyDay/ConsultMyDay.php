<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultMyDay;

/**
 * Reads the prospector's day and what comes after it. The day runs from its start until the start of the next day,
 * at the reader's offset. The query tells what each exchange offers at the reading moment.
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
