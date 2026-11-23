<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\LiftSuspicionInReturn;

use Prospecter\Core\PersonalData\Domain\Event\DuplicateSuspicionLifted;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * A contact no longer resembles another one, or was forgotten. In return, the other contact is no longer suspected of
 * being their duplicate. When the other contact was forgotten meanwhile, the reaction has nothing left to do. When it
 * no longer suspects them, it records nothing.
 */
final readonly class LiftSuspicionInReturnHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private PersonRepository $persons,
        private Events $events,
    ) {
    }

    public function __invoke(DuplicateSuspicionLifted $event): void
    {
        try {
            $contact = $this->persons->get($event->duplicate);
        } catch (UnknownPerson) {
            return;
        }

        $contact->liftSuspicionOf($event->person, by: $event->by, at: $event->occurredAt);
        $this->persons->save($contact);
        $this->publishWhatHappenedTo($contact);
    }
}
