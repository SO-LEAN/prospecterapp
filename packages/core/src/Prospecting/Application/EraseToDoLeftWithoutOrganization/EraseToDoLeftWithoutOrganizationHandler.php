<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\EraseToDoLeftWithoutOrganization;

use Prospecter\Core\Prospecting\Domain\Event\ToDoLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownToDo;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * A to-do kept for no organization is erased, with the personal data its text may hold, by the member who stopped
 * prospecting its organization. Delivered again once it is erased, the reaction has nothing left to do.
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
