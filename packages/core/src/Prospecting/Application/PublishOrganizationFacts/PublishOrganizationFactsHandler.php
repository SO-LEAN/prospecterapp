<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\PublishOrganizationFacts;

use Prospecter\Core\Prospecting\Application\Port\OrganizationFactsRetriever;
use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;
use Prospecter\Core\Prospecting\Domain\Event\OrganizationEvent;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * Tells the other contexts an organization's facts each time something happens to it. It reads them as the
 * organization's service does, before the commit: the unit of work gives them when the command is about to write them.
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
            $facts->contacts,
            $facts->municipalityCode,
            $facts->country,
            $event->occurredAt,
        ));
    }
}
