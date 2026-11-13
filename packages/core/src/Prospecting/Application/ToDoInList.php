<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

/**
 * A to-do as a list shows it.
 */
final readonly class ToDoInList
{
    /**
     * @param string|null  $personId        its contact, none for the organization itself
     * @param list<string> $organizationIds the organizations it is kept for
     * @param string       $dueOn           the day it is due, `Y-m-d`
     * @param string|null  $dueAt           the hour of that day, `H:i`, when said
     * @param string       $heldBy          the member who noted it, who holds it
     * @param list<string> $possibleIntents names of what its state offers now: ReviseToDo, TickOffToDo, UntickToDo, EraseToDo
     */
    public function __construct(
        public string $id,
        public ?string $personId,
        public array $organizationIds,
        public string $text,
        public string $dueOn,
        public ?string $dueAt,
        public ?\DateTimeImmutable $doneAt,
        public string $heldBy,
        public array $possibleIntents,
    ) {
    }

    public function isDone(): bool
    {
        return null !== $this->doneAt;
    }
}
