<?php

declare(strict_types=1);

namespace App\ToDos;

use App\ToDos\Resource\ToDoResource;
use Prospecter\Core\Prospecting\Application\PersonsShown;
use Prospecter\Core\Prospecting\Application\ToDo\ConsultToDo\ConsultToDoOutput;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * Presents a to-do with its contact and its holder named, as the core read them (ADR 0085, 0217).
 */
final class ToDoPresenter implements ConsultToDoOutput
{
    public private(set) ToDoResource $resource;

    public function __construct(private readonly ToDoResourceFactory $toDoResourceFactory)
    {
    }

    public function present(ToDoInList $toDo, PersonsShown $persons): void
    {
        $this->resource = $this->toDoResourceFactory->of($toDo, $persons);
    }
}
