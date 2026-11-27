<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\ConsultToDos;

use Prospecter\Core\Prospecting\Application\Port\ToDoListRetriever;
use Prospecter\Core\Prospecting\Application\ToDo\PersonsOfToDos;
use Prospecter\Core\Prospecting\Application\ToDo\ToDoListOutput;
use Prospecter\Core\Prospecting\Application\ToDoList;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ConsultToDosHandler implements QueryHandler
{
    public function __construct(
        private ToDoListRetriever $toDoListRetriever,
        private Prospectors $prospectors,
        private ContactOrganizations $contactOrganizations,
        private PersonsOfToDos $personsOfToDos,
    ) {
    }

    public function __invoke(ConsultToDos $query, ToDoListOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $contact = new ContactId($query->personId);
        $this->contactOrganizations->ensureIsAContactOfTheAccount($contact, $prospector->accountId);

        $toDos = ToDoList::inOrder($this->toDoListRetriever->forContact($contact, $prospector->accountId));

        $output->present($toDos, $this->personsOfToDos->shownTo($query->prospectorId, $toDos->toDos));
    }
}
