<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\NoteOrganizationToDo;

use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\Task;
use Prospecter\Core\Prospecting\Domain\Model\ToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class NoteOrganizationToDoHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ToDoRepository $toDos,
        private OrganizationRepository $organizations,
        private Events $events,
    ) {
    }

    public function __invoke(NoteOrganizationToDo $command): void
    {
        $organization = $this->organizations->get(new OrganizationId($command->organizationId));
        $toDo = ToDo::noted(
            new Gesture($organization->accountId(), new MemberId($command->prospectorId), $command->executedAt),
            new ToDoId($command->id),
            new OrganizationCorrespondent($organization->id()),
            new Task($command->text, new Due($command->dueOn, $command->dueAt)),
        );
        $this->toDos->save($toDo);
        $this->publishWhatHappenedTo($toDo);
    }
}
