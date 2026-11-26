<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\ConsultOrganizationToDos;

use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Application\ToDo\PersonsOfToDos;
use Prospecter\Core\Prospecting\Application\ToDo\ToDoListOutput;
use Prospecter\Core\Prospecting\Application\ToDoList;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

final readonly class ConsultOrganizationToDosHandler implements QueryHandler
{
    public function __construct(
        private ToDoListRetriever $toDoListRetriever,
        private OrganizationSheetRetriever $organizationSheetRetriever,
        private PersonsOfToDos $personsOfToDos,
    ) {
    }

    public function __invoke(ConsultOrganizationToDos $query, ToDoListOutput $output): void
    {
        $organization = $this->organizationSheetRetriever->get(new OrganizationId($query->organizationId));

        $toDos = ToDoList::inOrder($this->toDoListRetriever->keptFor(new OrganizationId($organization->id)));

        $output->present($toDos, $this->personsOfToDos->shownTo($query->prospectorId, $toDos->toDos));
    }
}
