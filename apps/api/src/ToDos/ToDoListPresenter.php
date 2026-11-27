<?php

declare(strict_types=1);

namespace App\ToDos;

use App\ToDos\Resource\ToDoLinksResource;
use App\ToDos\Resource\ToDoListLinksResource;
use Prospecter\Core\Prospecting\Application\ToDo\ToDoListOutput;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\Prospecting\Application\ToDoList;

/**
 * Presents what Prospecting shows of a list of to-dos: each to-do with its links, under the links its client gives
 * (ADR 0085, 0217).
 */
final class ToDoListPresenter implements ToDoListOutput
{
    public private(set) ToDoList $toDos;

    /** @var array<string, ToDoLinksResource> by to-do id */
    public private(set) array $links = [];

    public function __construct(
        private readonly ToDoResourceFactory $toDoResourceFactory,
        public readonly ToDoListLinksResource $listLinks,
    ) {
    }

    public function present(ToDoList $toDos): void
    {
        $this->toDos = $toDos;
        $this->links = array_combine(
            array_map(static fn (ToDoInList $toDo): string => $toDo->id, $toDos->toDos),
            array_map($this->toDoResourceFactory->linksOf(...), $toDos->toDos),
        );
    }
}
