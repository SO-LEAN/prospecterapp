<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DetachForgottenContact;

use Prospecter\Core\PersonalData\Application\Published\Event\ContactForgotten;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\OrphanContacts;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * An erased contact leaves every organization naming it, by the member who erased it: the integration event
 * PersonalData publishes, translated into the words of Prospecting (ADR 0046).
 */
final readonly class DetachForgottenContactHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private ProspectorService $prospectorService,
        private Events $events,
        private OrphanContacts $orphans,
    ) {
    }

    public function __invoke(ContactForgotten $event): void
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($event->memberId));
        $contact = new PersonId($event->personId);

        foreach ($this->organizations->withContacts([$contact], new AccountId($event->accountId)) as $organization) {
            $organization->removeContact($contact, by: $prospector, at: $event->occurredAt, orphans: $this->orphans);
            $this->organizations->save($organization);
            $this->publishWhatHappenedTo($organization);
        }
    }
}
