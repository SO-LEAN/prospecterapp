<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteToDo;

/**
 * Notes a to-do for a contact, kept for the organizations the contact is part of now, due on a day (`Y-m-d`), at an
 * hour of it (`H:i`) when said.
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
