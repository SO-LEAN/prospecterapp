<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SuspectDuplicateInReturn;

use Prospecter\Core\Prospecting\Domain\Event\DuplicateSuspected;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * The organization that a newcomer resembles is suspected in return of being its duplicate. When the organization is
 * no longer prospected, the reaction has nothing left to do. When it already suspects the newcomer, it records nothing.
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
