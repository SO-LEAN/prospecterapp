<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\MoveExchange;

/**
 * Moves a planned exchange to another moment to come, by its holder, for the duration given, else its own (ADR 0119).
 */
final readonly class MoveExchange
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public \DateTimeImmutable $plannedAt,
        public \DateTimeImmutable $executedAt,
        public ?int $durationInMinutes = null,
    ) {
    }
}
