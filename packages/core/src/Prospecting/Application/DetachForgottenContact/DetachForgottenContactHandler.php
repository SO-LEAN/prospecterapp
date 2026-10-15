<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DetachForgottenContact;

use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\OrphanContacts;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\Event\ContactForgotten;

/**
 * An erased contact leaves every organization naming it, by the member who erased it.
 */
final readonly class DetachForgottenContactHandler implements EventHandler
{
    private OrphanContacts $orphans;

    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
        $this->orphans = new OrphanContacts($organizations);
    }

    public function __invoke(ContactForgotten $event): void
    {
        $prospector = $this->prospectors->get($event->forgottenBy);

        foreach ($this->organizations->withContacts([$event->contact], $event->accountId) as $organization) {
            $organization->removeContact($event->contact, by: $prospector, at: $event->occurredAt, orphans: $this->orphans);
            $this->organizations->save($organization);
        }
    }
}
