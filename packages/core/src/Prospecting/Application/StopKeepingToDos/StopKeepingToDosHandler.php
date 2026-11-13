<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StopKeepingToDos;

use Prospecter\Core\Prospecting\Domain\Event\ProspectingStopped;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * An organization no longer prospected takes its to-dos along, as it does its exchanges: those still kept for another
 * organization are saved, each of the others left to its own reaction, that erases it
 * (EraseToDoLeftWithoutOrganization): one transaction holds a bounded number of writes.
 */
final readonly class StopKeepingToDosHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private ToDoRepository $toDos,
        private Events $events,
    ) {
    }

    public function __invoke(ProspectingStopped $event): void
    {
        foreach ($this->toDos->keptFor($event->organization) as $toDo) {
            $toDo->stopKeepingFor($event->organization, by: $event->by, at: $event->occurredAt);

            if ($toDo->isKeptForAnOrganization()) {
                $this->toDos->save($toDo);
            }

            $this->publishWhatHappenedTo($toDo);
        }
    }
}
