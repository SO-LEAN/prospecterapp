<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AttachContactToAttach;

use Prospecter\Core\Prospecting\Domain\Event\ContactAdded;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * Once a member adds a contact to attach to an organization, the contact is no longer to attach (ADR 0146). The
 * reaction does nothing for any other contact.
 */
final readonly class AttachContactToAttachHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(private ContactToAttachRepository $contactsToAttach, private Events $events)
    {
    }

    public function __invoke(ContactAdded $event): void
    {
        $contactToAttach = $this->contactsToAttach->find($event->contact);

        if (null === $contactToAttach) {
            return;
        }

        $contactToAttach->attachTo($event->organization, by: $event->by, at: $event->occurredAt);
        $this->contactsToAttach->remove($contactToAttach);
        $this->publishWhatHappenedTo($contactToAttach);
    }
}
