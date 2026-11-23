<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\KeepToDosOfAttachedContact;

use Prospecter\Core\Prospecting\Domain\Event\ContactAdded;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * The holder kept some to-dos while the contact waited to be attached. These to-dos follow the contact into the
 * organization they join (ADR 0146). The to-dos already kept for organizations stay as they are (ADR 0113).
 */
final readonly class KeepToDosOfAttachedContactHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(private ToDoRepository $toDos, private Events $events)
    {
    }

    public function __invoke(ContactAdded $event): void
    {
        foreach ($this->toDos->forContact($event->contact, $event->accountId) as $toDo) {
            if (!$toDo->isKeptForAnOrganization()) {
                $toDo->keepFor($event->organization, by: $event->by, at: $event->occurredAt);
                $this->toDos->save($toDo);
                $this->publishWhatHappenedTo($toDo);
            }
        }
    }
}
