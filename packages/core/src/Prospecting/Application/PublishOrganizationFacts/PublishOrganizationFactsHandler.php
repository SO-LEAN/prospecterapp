<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\PublishOrganizationFacts;

use Prospecter\Core\Prospecting\Application\Port\OrganizationFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;

/**
 * Tells the other contexts the facts of an organization each time something happens to it, read as its service
 * reads them, before the commit: the unit of work gives them as the command is about to write them.
 */
final readonly class PublishOrganizationFactsHandler implements EventHandler
{
    public function __construct(
        private OrganizationFactsRetriever $organizationFactsRetriever,
        private Events $events,
    ) {
    }

    public function __invoke(OrganizationEvent $event): void
    {
        $facts = $this->organizationFactsRetriever->of($event->organization);

        $this->events->publish(new OrganizationFactsChanged(
            (string) $event->accountId,
            $facts->organizationId,
            $facts->revision,
            $facts->present,
            $facts->located,
            $event->occurredAt,
        ));
    }
}
