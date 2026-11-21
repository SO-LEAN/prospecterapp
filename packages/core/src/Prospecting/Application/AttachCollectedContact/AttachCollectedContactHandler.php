<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AttachCollectedContact;

use Prospecter\Core\PersonalData\Contract\Event\ContactCollected;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact collected in PersonalData joins the organizations it was collected for, by its collector: the
 * integration event PersonalData publishes, translated into the words of Prospecting (ADR 0046). An organization a
 * client attached them to meanwhile, before the reaction ran, names them already: it stays as it is (ADR 0177).
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
        $contact = new PersonId($event->personId);

        foreach ($event->organizationIds as $organizationId) {
            $organization = $this->organizations->get(new OrganizationId($organizationId));

            if ($organization->hasContact($contact)) {
                continue;
            }

            $organization->addContact($contact, by: $collector, at: $event->occurredAt);
            $this->organizations->save($organization);
            $this->publishWhatHappenedTo($organization);
        }
    }
}
