<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DetachForgottenContact;

use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\OrphanContacts;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Event\ContactForgotten;

/**
 * An erased contact leaves every organization naming it, by the member who erased it.
 */
final readonly class DetachForgottenContactHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Prospectors $prospectors,
        private Events $events,
        private OrphanContacts $orphans,
    ) {
    }

    public function __invoke(ContactForgotten $event): void
    {
        $prospector = $this->prospectors->get($event->by);

        foreach ($this->organizations->withContacts([$event->person], $event->accountId) as $organization) {
            $organization->removeContact($event->person, by: $prospector, at: $event->occurredAt, orphans: $this->orphans);
            $this->organizations->save($organization);
            $this->publishWhatHappenedTo($organization);
        }
    }
}
