<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\UntickToDo;

/**
 * Unticks a to-do ticked off by mistake: it is to do again.
 */
final readonly class UntickToDo
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
