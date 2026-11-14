<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\CheckMyConnections;

use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class CheckMyConnectionsHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ConnectionRepository $connections,
        private ProviderAccess $providerAccess,
        private Events $events,
    ) {
    }

    public function __invoke(CheckMyConnections $command): void
    {
        foreach ($this->connections->of(new MemberId($command->memberId)) as $connection) {
            $connection->check($this->providerAccess, $command->executedAt);
            $this->connections->save($connection);
            $this->publishWhatHappenedTo($connection);
        }
    }
}
