<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteOrganizationToDo;

/**
 * Notes a to-do for the organization itself, kept for it alone, due on a day (`Y-m-d`), at an hour of it (`H:i`) when
 * said.
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
