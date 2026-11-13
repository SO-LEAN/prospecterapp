<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptToDoList implements ToDoListOutput
{
    public private(set) ToDoList $toDos;

    public function present(ToDoList $toDos): void
    {
        $this->toDos = $toDos;
    }
}
