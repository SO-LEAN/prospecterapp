<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\EraseToDo;

/**
 * Erases a to-do logged by mistake, or one that no longer has a purpose.
 */
final readonly class EraseToDo
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
