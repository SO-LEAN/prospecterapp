<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo;

use Prospecter\Core\Prospecting\Application\PersonsShown;
use Prospecter\Core\Prospecting\Application\ToDoList;
use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ToDoListOutput extends OutputPort
{
    /**
     * @param PersonsShown $persons the to-dos' contacts and holders
     */
    public function present(ToDoList $toDos, PersonsShown $persons): void;
}
