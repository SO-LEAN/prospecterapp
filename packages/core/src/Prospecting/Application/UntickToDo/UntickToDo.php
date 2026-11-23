<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\UntickToDo;

/**
 * Unticks a to-do that was ticked off by mistake. It is to do again.
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
