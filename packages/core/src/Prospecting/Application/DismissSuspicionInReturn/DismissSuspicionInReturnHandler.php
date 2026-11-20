<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DismissSuspicionInReturn;

use Prospecter\Core\Prospecting\Domain\Event\DuplicateSuspicionDismissed;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * The organization a member said is no duplicate of another is no duplicate of it in return, by the same member.
 * No longer prospected meanwhile, the reaction has nothing left to do; knowing it already, the organization records
 * nothing.
 */
final readonly class DismissSuspicionInReturnHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Events $events,
    ) {
    }

    public function __invoke(DuplicateSuspicionDismissed $event): void
    {
        try {
            $organization = $this->organizations->get($event->duplicate);
        } catch (UnknownOrganization) {
            return;
        }

        $organization->knowItIsNoDuplicateOf($event->organization, by: $event->by, at: $event->occurredAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
