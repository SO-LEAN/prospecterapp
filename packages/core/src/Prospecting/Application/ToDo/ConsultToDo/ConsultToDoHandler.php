<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\ConsultToDo;

use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Application\ToDo\PersonsOfToDos;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

final readonly class ConsultToDoHandler implements QueryHandler
{
    public function __construct(
        private ToDoListRetriever $toDoListRetriever,
        private PersonsOfToDos $personsOfToDos,
    ) {
    }

    public function __invoke(ConsultToDo $query, ConsultToDoOutput $output): void
    {
        $toDo = $this->toDoListRetriever->get(new ToDoId($query->id));

        $output->present($toDo, $this->personsOfToDos->shownTo($query->prospectorId, [$toDo]));
    }
}
