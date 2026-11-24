<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DetachForgottenContact;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\OrphanContacts;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * An erased contact leaves every organization that names it, and the member who erased it is the author.
 */
final readonly class DetachForgottenContactHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private OrganizationRepository $organizations,
        private Events $events,
        private OrphanContacts $orphans,
    ) {
    }

    public function __invoke(DetachForgottenContact $command): void
    {
        $author = new MemberId($command->memberId);
        $contact = new ContactId($command->contactId);

        foreach ($this->organizations->withContacts([$contact], new AccountId($command->accountId)) as $organization) {
            $organization->removeContact($contact, by: $author, at: $command->executedAt, orphans: $this->orphans);
            $this->organizations->save($organization);
            $this->publishWhatHappenedTo($organization);
        }
    }
}
