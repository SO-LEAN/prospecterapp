<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\SuspectDuplicateInReturn;

use Prospecter\Core\PersonalData\Domain\Event\DuplicateSuspected;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownPerson;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * The contact a newcomer resembles is suspected of being their duplicate in return. Forgotten meanwhile, the reaction
 * has nothing left to do; suspecting them already, the contact records nothing.
 */
final readonly class SuspectDuplicateInReturnHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private PersonRepository $persons,
        private Events $events,
    ) {
    }

    public function __invoke(DuplicateSuspected $event): void
    {
        try {
            $contact = $this->persons->get($event->duplicate);
        } catch (UnknownPerson) {
            return;
        }

        $contact->suspectDuplicateOf($event->person, by: $event->by, at: $event->occurredAt);
        $this->persons->save($contact);
        $this->publishWhatHappenedTo($contact);
    }
}
