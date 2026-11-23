<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\EraseToDoLeftWithoutOrganization;

use Prospecter\Core\Prospecting\Domain\Event\ToDoLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownToDo;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * Erases a to-do kept for no organization, with the personal data its text may hold. The member who stopped
 * prospecting its organization is the author. When the event is delivered again after the erasure, the reaction has
 * nothing left to do.
 */
final readonly class EraseToDoLeftWithoutOrganizationHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private ToDoRepository $toDos,
        private Events $events,
    ) {
    }

    public function __invoke(ToDoLeftWithoutOrganization $event): void
    {
        try {
            $toDo = $this->toDos->get($event->toDo);
        } catch (UnknownToDo) {
            return;
        }

        $toDo->erase(by: $event->by, at: $event->occurredAt);
        $this->toDos->remove($toDo);
        $this->publishWhatHappenedTo($toDo);
    }
}
