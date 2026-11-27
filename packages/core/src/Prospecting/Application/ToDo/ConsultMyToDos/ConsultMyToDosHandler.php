<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\ConsultMyToDos;

use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Application\ToDo\ToDoListOutput;
use Prospecter\Core\Prospecting\Application\ToDoList;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ConsultMyToDosHandler implements QueryHandler
{
    public function __construct(
        private ToDoListRetriever $toDoListRetriever,
    ) {
    }

    public function __invoke(ConsultMyToDos $query, ToDoListOutput $output): void
    {
        $output->present(ToDoList::inOrder($this->toDoListRetriever->stillToDoBy(new MemberId($query->prospectorId))));
    }
}
