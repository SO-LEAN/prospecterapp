<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\EraseToDosOfForgottenContact;

use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\Prospecting\Domain\Repository\ToDoRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The to-dos of an erased contact are erased, by the member who erased it: unlike its exchanges, they are no
 * commercial history to keep, and their text may hold its personal data (ADR 0113).
 */
final readonly class EraseToDosOfForgottenContactHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private ToDoRepository $toDos,
        private Events $events,
    ) {
    }

    public function __invoke(ContactForgotten $event): void
    {
        $author = new MemberId($event->memberId);

        foreach ($this->toDos->forContact(new PersonId($event->personId), new AccountId($event->accountId)) as $toDo) {
            $toDo->erase(by: $author, at: $event->occurredAt);
            $this->toDos->remove($toDo);
            $this->publishWhatHappenedTo($toDo);
        }
    }
}
