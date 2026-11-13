<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultToDo;

use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

final readonly class ConsultToDoHandler implements QueryHandler
{
    public function __construct(
        private ToDoListRetriever $toDoListRetriever,
    ) {
    }

    public function __invoke(ConsultToDo $query, ConsultToDoOutput $output): void
    {
        $output->present($this->toDoListRetriever->get(new ToDoId($query->id)));
    }
}
