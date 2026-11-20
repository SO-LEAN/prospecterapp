<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\LiftSuspicionInReturn;

use Prospecter\Core\Prospecting\Domain\Event\DuplicateSuspicionLifted;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * The organization an organization no longer resembles is no longer suspected of being its duplicate in return.
 * No longer prospected meanwhile, the reaction has nothing left to do; no longer suspecting it, the organization
 * records nothing.
 */
final readonly class LiftSuspicionInReturnHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Events $events,
    ) {
    }

    public function __invoke(DuplicateSuspicionLifted $event): void
    {
        try {
            $organization = $this->organizations->get($event->duplicate);
        } catch (UnknownOrganization) {
            return;
        }

        $organization->liftSuspicionOf($event->organization, by: $event->by, at: $event->occurredAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
