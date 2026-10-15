<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RemoveContact;

use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\OrphanContacts;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Detaches the contact; from its last organization, the event the organization records erases it in
 * PersonalData (ContactsLeftWithoutOrganization).
 */
final readonly class RemoveContactHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private ProspectorService $prospectorService,
        private Events $events,
        private OrphanContacts $orphans,
    ) {
    }

    public function __invoke(RemoveContact $command): void
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($command->prospectorId));
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->removeContact(new PersonId($command->personId), by: $prospector, at: $command->executedAt, orphans: $this->orphans);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
