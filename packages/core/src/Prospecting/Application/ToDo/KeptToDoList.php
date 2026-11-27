<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo;

use Prospecter\Core\Prospecting\Application\PersonsShown;
use Prospecter\Core\Prospecting\Application\ToDoList;

/**
 * Keeps the answer as it is, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptToDoList implements ToDoListOutput
{
    public private(set) ToDoList $toDos;
    public private(set) PersonsShown $persons;

    public function present(ToDoList $toDos, PersonsShown $persons): void
    {
        $this->toDos = $toDos;
        $this->persons = $persons;
    }
}
