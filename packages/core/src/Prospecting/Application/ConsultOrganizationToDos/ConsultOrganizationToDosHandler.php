<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganizationToDos;

use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Application\ToDoList;
use Prospecter\Core\Prospecting\Application\ToDoListOutput;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class ConsultOrganizationToDosHandler implements QueryHandler
{
    public function __construct(
        private ToDoListRetriever $toDoListRetriever,
        private OrganizationSheetRetriever $organizationSheetRetriever,
    ) {
    }

    public function __invoke(ConsultOrganizationToDos $query, ToDoListOutput $output): void
    {
        $organization = $this->organizationSheetRetriever->get(new OrganizationId($query->organizationId));

        $output->present(ToDoList::inOrder($this->toDoListRetriever->keptFor(new OrganizationId($organization->id))));
    }
}
