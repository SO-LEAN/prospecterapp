<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DismissSuspicionInReturn;

use Prospecter\Core\Prospecting\Domain\Event\DuplicateSuspicionDismissed;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * A member stated that an organization is not a duplicate of another. In return, the other organization is not a
 * duplicate of the first, by the same member. When the account stopped prospecting it meanwhile, the reaction has
 * nothing left to do. When the organization already knows it, it records nothing.
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
