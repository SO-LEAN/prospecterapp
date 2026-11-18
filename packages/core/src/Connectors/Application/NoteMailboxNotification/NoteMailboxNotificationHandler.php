<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\NoteMailboxNotification;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;

final readonly class NoteMailboxNotificationHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ConnectionRepository $connections,
        private Events $events,
    ) {
    }

    /**
     * A mailbox no member of the account imports is none of its business: nothing is done.
     */
    public function __invoke(NoteMailboxNotification $command): void
    {
        $connection = $this->connections->importing(new AccountId($command->accountId), Provider::from($command->provider), new Email($command->mailbox));
        if (null === $connection) {
            return;
        }

        $connection->noteMailboxChange($command->historyId, $command->executedAt);
        $this->connections->save($connection);
        $this->publishWhatHappenedTo($connection);
    }
}
