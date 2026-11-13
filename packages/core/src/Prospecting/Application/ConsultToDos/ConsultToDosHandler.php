<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultToDos;

use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Application\ToDoList;
use Prospecter\Core\Prospecting\Application\ToDoListOutput;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ConsultToDosHandler implements QueryHandler
{
    public function __construct(
        private ToDoListRetriever $toDoListRetriever,
        private Prospectors $prospectors,
        private ContactOrganizations $contactOrganizations,
    ) {
    }

    public function __invoke(ConsultToDos $query, ToDoListOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $contact = new PersonId($query->personId);
        $this->contactOrganizations->ensureIsAContactOfTheAccount($contact, $prospector->accountId);

        $output->present(ToDoList::inOrder($this->toDoListRetriever->forContact($contact, $prospector->accountId)));
    }
}
