<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RemoveContact;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\OrphanContacts;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Detaches the contact. When it leaves its last organization, the event that the organization records erases it in
 * PersonalData (ContactsLeftWithoutOrganization).
 */
final readonly class RemoveContactHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Events $events,
        private OrphanContacts $orphans,
    ) {
    }

    public function __invoke(RemoveContact $command): void
    {
        $author = new MemberId($command->prospectorId);
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->removeContact(new ContactId($command->personId), by: $author, at: $command->executedAt, orphans: $this->orphans);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }
}
