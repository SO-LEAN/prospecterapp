<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteToDo;

use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\ToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class NoteToDoHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ToDoRepository $toDos,
        private Prospectors $prospectors,
        private ContactOrganizations $contactOrganizations,
        private Events $events,
    ) {
    }

    public function __invoke(NoteToDo $command): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($command->prospectorId));
        $contact = new PersonId($command->personId);
        $toDo = ToDo::forAContact(
            $prospector->accountId,
            $prospector->id,
            new ToDoId($command->id),
            $contact,
            $this->contactOrganizations->of($contact, $prospector->accountId),
            $command->text,
            new Due($command->dueOn, $command->dueAt),
            $command->executedAt,
        );
        $this->toDos->save($toDo);
        $this->publishWhatHappenedTo($toDo);
    }
}
