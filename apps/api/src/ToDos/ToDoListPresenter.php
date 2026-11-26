<?php

declare(strict_types=1);

namespace App\ToDos;

use App\ToDos\Resource\ToDoListLinksResource;
use App\ToDos\Resource\ToDoListResource;
use App\ToDos\Resource\ToDoResource;
use Prospecter\Core\Prospecting\Application\PersonsShown;
use Prospecter\Core\Prospecting\Application\ToDo\ToDoListOutput;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Application\ToDoList;

/**
 * Presents a list of to-dos, each with its contact and its holder named, under the links its client gives (ADR 0085,
 * 0217).
 */
final class ToDoListPresenter implements ToDoListOutput
{
    public private(set) ToDoListResource $resource;

    public function __construct(
        private readonly ToDoResourceFactory $toDoResourceFactory,
        private readonly ToDoListLinksResource $links,
    ) {
    }

    public function present(ToDoList $toDos, PersonsShown $persons): void
    {
        $this->resource = new ToDoListResource(
            array_map(fn (ToDoInList $toDo): ToDoResource => $this->toDoResourceFactory->of($toDo, $persons), $toDos->toDos),
            $this->links,
        );
    }
}
