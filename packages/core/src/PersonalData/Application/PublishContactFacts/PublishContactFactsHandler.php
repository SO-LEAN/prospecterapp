<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\PublishContactFacts;

use Prospecter\Core\PersonalData\Application\Port\ContactFactsRetriever;
use Prospecter\Core\PersonalData\Contract\Event\ContactFactsChanged;
use Prospecter\Core\PersonalData\Domain\Event\PersonEvent;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * Tells the other contexts a contact's facts each time something happens to the contact. It reads the facts as the
 * contact's service reads them, before the commit. The unit of work gives them as the command is about to write them.
 * A member's profile is not a contact, so the handler publishes nothing for it.
 */
final readonly class PublishContactFactsHandler implements EventHandler
{
    public function __construct(
        private ContactFactsRetriever $contactFactsRetriever,
        private Events $events,
    ) {
    }

    public function __invoke(PersonEvent $event): void
    {
        $facts = $this->contactFactsRetriever->of($event->person);

        if (null === $facts) {
            return;
        }

        $this->events->publish(new ContactFactsChanged(
            (string) $event->accountId,
            $facts->personId,
            $facts->revision,
            $facts->present,
            $facts->hasEmail,
            $facts->hasPhone,
            $facts->hasMobile,
            $facts->hasLinkedIn,
            $event->occurredAt,
        ));
    }
}
