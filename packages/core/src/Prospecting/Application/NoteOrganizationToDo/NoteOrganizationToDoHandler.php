<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteOrganizationToDo;

use Prospecter\Core\Prospecting\Domain\Model\Due;
use Prospecter\Core\Prospecting\Domain\Model\ToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

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
        $toDo = ToDo::forTheOrganization(
            $this->organizations->get(new OrganizationId($command->organizationId)),
            new MemberId($command->prospectorId),
            new ToDoId($command->id),
            $command->text,
            new Due($command->dueOn, $command->dueAt),
            $command->executedAt,
        );
        $this->toDos->save($toDo);
        $this->publishWhatHappenedTo($toDo);
    }
}
