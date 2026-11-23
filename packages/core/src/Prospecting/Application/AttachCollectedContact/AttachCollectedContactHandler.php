<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AttachCollectedContact;

use Prospecter\Core\PersonalData\Contract\Event\ContactCollected;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reacts to a contact collected in PersonalData. The contact joins the organizations it was collected for, and its
 * collector is the author. The reaction translates PersonalData's integration event into Prospecting's words
 * (ADR 0046). A client may have attached the contact to an organization before the reaction ran. That organization
 * already names the contact, so it stays as it is (ADR 0177).
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
