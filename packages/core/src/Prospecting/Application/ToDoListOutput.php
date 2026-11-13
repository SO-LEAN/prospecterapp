<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ToDoListOutput extends OutputPort
{
    public function present(ToDoList $toDos): void;
}
