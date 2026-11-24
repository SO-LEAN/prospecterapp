<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\AttachContactToAttach;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Once a member adds a contact to attach to an organization, the contact is no longer to attach (ADR 0146). The
 * command does nothing for any other contact.
 */
final readonly class AttachContactToAttachHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private ContactToAttachRepository $contactsToAttach, private Events $events)
    {
    }

    public function __invoke(AttachContactToAttach $command): void
    {
        $contactToAttach = $this->contactsToAttach->find(new ContactId($command->contactId));

        if (null === $contactToAttach) {
            return;
        }

        $contactToAttach->attachTo(new OrganizationId($command->organizationId), by: new MemberId($command->memberId), at: $command->executedAt);
        $this->contactsToAttach->remove($contactToAttach);
        $this->publishWhatHappenedTo($contactToAttach);
    }
}
