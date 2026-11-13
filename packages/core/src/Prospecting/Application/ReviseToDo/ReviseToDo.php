<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ReviseToDo;

/**
 * Changes the text of a to-do and when it is due, on a day (`Y-m-d`), at an hour of it (`H:i`) when said.
 */
final readonly class ReviseToDo
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public string $text,
        public string $dueOn,
        public ?string $dueAt,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
