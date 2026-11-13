<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\TickOffToDo;

/**
 * Ticks a to-do off, done at the moment of the command.
 */
final readonly class TickOffToDo
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
