<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteOrganizationToDo;

/**
 * Logs a to-do for the organization itself. The to-do is kept for that organization only. It is due on a day
 * (`Y-m-d`), and at an hour of that day (`H:i`) when one is given.
 */
final readonly class NoteOrganizationToDo
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public string $organizationId,
        public string $text,
        public string $dueOn,
        public ?string $dueAt,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
