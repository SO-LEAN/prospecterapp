<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\NoteToDo;

use Prospecter\Core\Prospecting\Domain\Model\ContactCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\Task;
use Prospecter\Core\Prospecting\Domain\Model\ToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

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
        $contact = new ContactId($command->personId);
        $toDo = ToDo::noted(
            new Gesture($prospector->accountId, $prospector->id, $command->executedAt),
            new ToDoId($command->id),
            new ContactCorrespondent($contact, $this->contactOrganizations->keepersOf($contact, $prospector)),
            new Task($command->text, new Due($command->dueOn, $command->dueAt)),
        );
        $this->toDos->save($toDo);
        $this->publishWhatHappenedTo($toDo);
    }
}
