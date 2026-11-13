<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\EraseToDo;

/**
 * Erases a to-do noted by mistake, or left without purpose.
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
