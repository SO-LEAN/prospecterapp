<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultToDo;

use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptToDo implements ConsultToDoOutput
{
    public private(set) ToDoInList $toDo;

    public function present(ToDoInList $toDo): void
    {
        $this->toDo = $toDo;
    }
}
