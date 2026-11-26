<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\ConsultToDo;

use Prospecter\Core\Prospecting\Application\PersonsShown;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * Keeps the answer unchanged, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptToDo implements ConsultToDoOutput
{
    public private(set) ToDoInList $toDo;
    public private(set) PersonsShown $persons;

    public function present(ToDoInList $toDo, PersonsShown $persons): void
    {
        $this->toDo = $toDo;
        $this->persons = $persons;
    }
}
