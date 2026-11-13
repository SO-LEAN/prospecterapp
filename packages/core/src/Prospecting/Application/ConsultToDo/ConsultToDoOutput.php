<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultToDo;

use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultToDoOutput extends OutputPort
{
    public function present(ToDoInList $toDo): void;
}
