<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Disconnect;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class DisconnectHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ConnectionRepository $connections,
        private ProviderAccess $providerAccess,
        private ProviderClientRepository $clients,
        private ProviderMailbox $mailbox,
        private Members $members,
        private Events $events,
    ) {
    }

    public function __invoke(Disconnect $command): void
    {
        $me = new MemberId($command->memberId);
        $provider = Provider::from($command->provider);
        $connection = $this->connections->get($me, $provider);
        $connection->stopMailImport($this->clients->find($this->members->get($me)->accountId, $provider)?->credentials(), $this->mailbox, $command->executedAt);
        $connection->disconnect($this->providerAccess, $command->executedAt);
        $this->connections->remove($connection);
        $this->publishWhatHappenedTo($connection);
    }
}
