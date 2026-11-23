<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\MarkExchangeDone;

/**
 * The holder marks a planned exchange done once its time has come. The holder gives the moment it really took place
 * and its report. The report replaces its agenda.
 */
final readonly class MarkExchangeDone
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public \DateTimeImmutable $occurredAt,
        public ?string $report,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
