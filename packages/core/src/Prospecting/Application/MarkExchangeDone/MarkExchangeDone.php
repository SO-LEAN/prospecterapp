<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\MarkExchangeDone;

/**
 * Marks done a planned exchange whose time has come, by its holder: the moment it really took place, and its
 * report, which replaces its agenda.
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
