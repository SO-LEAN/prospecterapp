<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AttachCollectedContact;

use Prospecter\Core\PersonalData\Contract\Event\ContactCollected;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact collected in PersonalData joins the organizations it was collected for, by its collector: the
 * integration event PersonalData publishes, translated into the words of Prospecting (ADR 0046).
 */
final readonly class AttachCollectedContactHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(private OrganizationRepository $organizations, private Events $events)
    {
    }

    public function __invoke(ContactCollected $event): void
    {
        $collector = new MemberId($event->memberId);

        foreach ($event->organizationIds as $organizationId) {
            $organization = $this->organizations->get(new OrganizationId($organizationId));
            $organization->addContact(new PersonId($event->personId), by: $collector, at: $event->occurredAt);
            $this->organizations->save($organization);
            $this->publishWhatHappenedTo($organization);
        }
    }
}
