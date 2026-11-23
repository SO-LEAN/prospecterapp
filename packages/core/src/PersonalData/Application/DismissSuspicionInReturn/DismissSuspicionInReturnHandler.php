<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\DismissSuspicionInReturn;

use Prospecter\Core\PersonalData\Domain\Event\DuplicateSuspicionDismissed;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * A member checked that a contact is not a duplicate of another one. In return, the other contact is not a duplicate
 * of the first one either. When the contact was forgotten meanwhile, the reaction has nothing left to do. When the
 * contact knows it already, it records nothing.
 */
final readonly class DismissSuspicionInReturnHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private PersonRepository $persons,
        private Events $events,
    ) {
    }

    public function __invoke(DuplicateSuspicionDismissed $event): void
    {
        try {
            $contact = $this->persons->get($event->duplicate);
        } catch (UnknownPerson) {
            return;
        }

        $contact->knowItIsNoDuplicateOf($event->person, by: $event->by, at: $event->occurredAt);
        $this->persons->save($contact);
        $this->publishWhatHappenedTo($contact);
    }
}
