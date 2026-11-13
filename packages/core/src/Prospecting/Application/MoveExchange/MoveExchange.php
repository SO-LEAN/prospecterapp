<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\MoveExchange;

/**
 * Moves a planned exchange to another moment to come, by its holder; its duration stays.
 */
final readonly class MoveExchange
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public \DateTimeImmutable $plannedAt,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
