<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\EraseContactLeftUnattached;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * Erases a contact only once, even when it is asked again for the same contact: by a schedule that runs twice, or
 * by two runs at once.
 */
final readonly class EraseContactLeftUnattachedHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private ContactToAttachRepository $contactsToAttach, private Events $events)
    {
    }

    public function __invoke(EraseContactLeftUnattached $command): void
    {
        $contactToAttach = $this->contactsToAttach->find(new ContactId($command->personId));

        if ($contactToAttach instanceof ContactToAttach && $contactToAttach->isLeftUnattachedAt($command->executedAt)) {
            $contactToAttach->eraseLeftUnattached($command->executedAt);
            $this->contactsToAttach->remove($contactToAttach);
            $this->publishWhatHappenedTo($contactToAttach);
        }
    }
}
