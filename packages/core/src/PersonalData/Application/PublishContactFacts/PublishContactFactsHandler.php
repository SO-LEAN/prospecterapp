<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\PublishContactFacts;

use Prospecter\Core\PersonalData\Application\Port\ContactFactsRetriever;
use Prospecter\Core\PersonalData\Contract\Event\ContactFactsChanged;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Event\PersonEvent;

/**
 * Tells the other contexts the facts of a contact each time something happens to it, read as its service reads
 * them, before the commit: the unit of work gives them as the command is about to write them. The profile of a
 * member is no contact: it publishes nothing.
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
            $event->occurredAt,
        ));
    }
}
