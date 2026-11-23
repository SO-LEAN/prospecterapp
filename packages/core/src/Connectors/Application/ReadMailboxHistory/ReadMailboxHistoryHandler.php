<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ReadMailboxHistory;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads the mailbox history from the point where it was last read. This runs once the mailbox notified a change, or
 * once its watch was renewed. Each added message is imported on its own (ADR 0154), as long as the member manages the
 * personal data. Nothing is read for a member who is no longer connected, or whose account lost its client.
 */
final readonly class ReadMailboxHistoryHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ConnectionRepository $connections,
        private ProviderClientRepository $clients,
        private ProviderMailbox $mailbox,
        private Members $members,
        private Events $events,
    ) {
    }

    public function __invoke(ReadMailboxHistory $command): void
    {
        $member = new MemberId($command->memberId);
        $connection = $this->connections->find($member, Provider::Google);
        $client = $this->clients->find(new AccountId($command->accountId), Provider::Google);
        if (null === $connection || null === $client) {
            return;
        }

        $connection->readMailboxHistory($this->members->get($member), $client->credentials(), $this->mailbox, $command->executedAt);
        $this->connections->save($connection);
        $this->publishWhatHappenedTo($connection);
    }
}
