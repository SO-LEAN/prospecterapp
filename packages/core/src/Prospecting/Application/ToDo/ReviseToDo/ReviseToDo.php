<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\ReviseToDo;

/**
 * Changes a to-do's text and when it is due: on a day (`Y-m-d`), and at an hour of that day (`H:i`) when one is given.
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
