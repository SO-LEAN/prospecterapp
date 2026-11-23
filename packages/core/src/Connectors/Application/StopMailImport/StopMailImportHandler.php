<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\StopMailImport;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class StopMailImportHandler implements CommandHandler
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

    public function __invoke(StopMailImport $command): void
    {
        $me = new MemberId($command->memberId);
        $provider = Provider::from($command->provider);
        $connection = $this->connections->get($me, $provider);
        $connection->stopMailImport($this->clients->find($this->members->get($me)->accountId, $provider)?->credentials(), $this->mailbox, $command->executedAt);
        $this->connections->save($connection);
        $this->publishWhatHappenedTo($connection);
    }
}
