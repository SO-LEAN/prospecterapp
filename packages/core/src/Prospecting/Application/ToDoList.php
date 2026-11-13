<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

/**
 * The to-dos of a contact, of an organization or of a prospector, a handful each, read whole (ADR 0112): those to do
 * the soonest due first, a day without an hour before its hours, then those done, the last done first.
 */
final readonly class ToDoList
{
    /**
     * @param list<ToDoInList> $toDos
     */
    private function __construct(public array $toDos)
    {
    }

    /**
     * @param list<ToDoInList> $toDos
     */
    public static function inOrder(array $toDos): self
    {
        usort($toDos, static fn (ToDoInList $a, ToDoInList $b): int => $a->isDone() <=> $b->isDone() ?: self::lastDoneFirst($a, $b) ?: self::soonestDueFirst($a, $b));

        return new self($toDos);
    }

    private static function lastDoneFirst(ToDoInList $a, ToDoInList $b): int
    {
        return $b->doneAt <=> $a->doneAt;
    }

    private static function soonestDueFirst(ToDoInList $a, ToDoInList $b): int
    {
        return self::due($a) <=> self::due($b);
    }

    private static function due(ToDoInList $toDo): string
    {
        return sprintf('%s %s', $toDo->dueOn, $toDo->dueAt ?? '');
    }
}
