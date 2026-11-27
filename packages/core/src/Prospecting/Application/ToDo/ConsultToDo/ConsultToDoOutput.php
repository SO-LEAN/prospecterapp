<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\ConsultToDo;

use Prospecter\Core\Prospecting\Application\PersonsShown;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultToDoOutput extends OutputPort
{
    /**
     * @param PersonsShown $persons the to-do's contact and holder
     */
    public function present(ToDoInList $toDo, PersonsShown $persons): void;
}
