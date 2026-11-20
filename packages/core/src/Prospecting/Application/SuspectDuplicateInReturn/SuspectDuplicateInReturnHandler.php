<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SuspectDuplicateInReturn;

use Prospecter\Core\Prospecting\Domain\Event\DuplicateSuspected;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * The organization a newcomer resembles is suspected of being its duplicate in return. No longer prospected
 * meanwhile, the reaction has nothing left to do; suspecting it already, the organization records nothing.
 */
final readonly class SuspectDuplicateInReturnHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Events $events,
    ) {
    }

    public function __invoke(DuplicateSuspected $event): void
    {
        try {
            $organization = $this->organizations->get($event->duplicate);
        } catch (UnknownOrganization) {
            return;
        }

        $organization->suspectDuplicateOf($event->organization, by: $event->by, at: $event->occurredAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
