<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\NoteToDo;

/**
 * Logs a to-do for a contact. The to-do is kept for the organizations the contact is part of now. It is due on a day
 * (`Y-m-d`), and at an hour of that day (`H:i`) when one is given.
 */
final readonly class NoteToDo
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public string $personId,
        public string $text,
        public string $dueOn,
        public ?string $dueAt,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
