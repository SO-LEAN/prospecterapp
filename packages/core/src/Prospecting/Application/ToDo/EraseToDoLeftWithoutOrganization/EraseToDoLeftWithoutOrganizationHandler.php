<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\EraseToDoLeftWithoutOrganization;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownToDo;
use Prospecter\Core\Prospecting\Domain\Model\ToDoId;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Erases a to-do kept for no organization, with the personal data its text may hold. The member who stopped
 * prospecting its organization is the author. When the command comes again after the erasure, it has nothing left
 * to do.
 */
final readonly class EraseToDoLeftWithoutOrganizationHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ToDoRepository $toDos,
        private Events $events,
    ) {
    }

    public function __invoke(EraseToDoLeftWithoutOrganization $command): void
    {
        try {
            $toDo = $this->toDos->get(new ToDoId($command->toDoId));
        } catch (UnknownToDo) {
            return;
        }

        $toDo->erase(by: new MemberId($command->memberId), at: $command->executedAt);
        $this->toDos->remove($toDo);
        $this->publishWhatHappenedTo($toDo);
    }
}
