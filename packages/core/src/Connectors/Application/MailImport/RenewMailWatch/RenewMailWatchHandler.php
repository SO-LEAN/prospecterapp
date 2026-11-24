<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\MailImport\RenewMailWatch;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class RenewMailWatchHandler implements CommandHandler
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

    /**
     * Nothing is renewed for a member who is no longer connected, or whose account lost its client.
     */
    public function __invoke(RenewMailWatch $command): void
    {
        $member = new MemberId($command->memberId);
        $provider = Provider::from($command->provider);
        $connection = $this->connections->find($member, $provider);
        $client = $this->clients->find($this->members->get($member)->accountId, $provider);
        if (null === $connection || null === $client) {
            return;
        }

        $connection->renewMailWatch($client, $this->mailbox, $command->executedAt);
        $this->connections->save($connection);
        $this->publishWhatHappenedTo($connection);
    }
}
