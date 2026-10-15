<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AttachCollectedContact;

use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\Event\ContactCollected;

/**
 * A contact collected in PersonalData joins the organizations it was collected for, by its collector.
 */
final readonly class AttachCollectedContactHandler implements EventHandler
{
    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
    }

    public function __invoke(ContactCollected $event): void
    {
        $collector = $this->prospectors->get($event->collectedBy);

        foreach ($event->organizations as $organizationId) {
            $organization = $this->organizations->get($organizationId);
            $organization->addContact($event->contact, by: $collector, at: $event->occurredAt);
            $this->organizations->save($organization);
        }
    }
}
