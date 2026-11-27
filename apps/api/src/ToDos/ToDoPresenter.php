<?php

declare(strict_types=1);

namespace App\ToDos;

use App\ToDos\Resource\ToDoLinksResource;
use Prospecter\Core\Prospecting\Application\ToDo\ConsultToDo\ConsultToDoOutput;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * Presents what Prospecting shows of a to-do: the to-do and its links (ADR 0085, 0217).
 */
final class ToDoPresenter implements ConsultToDoOutput
{
    public private(set) ToDoInList $toDo;

    public private(set) ToDoLinksResource $links;

    public function __construct(private readonly ToDoResourceFactory $toDoResourceFactory)
    {
    }

    public function present(ToDoInList $toDo): void
    {
        $this->toDo = $toDo;
        $this->links = $this->toDoResourceFactory->linksOf($toDo);
    }
}
