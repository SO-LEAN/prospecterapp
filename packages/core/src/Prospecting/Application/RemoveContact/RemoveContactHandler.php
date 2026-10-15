<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RemoveContact;

use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\OrphanContacts;
use Prospecter\Core\Prospecting\Domain\Prospectors;
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

    private OrphanContacts $orphans;

    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors, private Events $events)
    {
        $this->orphans = new OrphanContacts($organizations);
    }

    public function __invoke(RemoveContact $command): void
    {
        $prospector = $this->prospectors->get(new MemberId($command->prospectorId));
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->removeContact(new PersonId($command->personId), by: $prospector, at: $command->executedAt, orphans: $this->orphans);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
