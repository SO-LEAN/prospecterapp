<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Disconnect;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class DisconnectHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ConnectionRepository $connections,
        private ProviderAccess $providerAccess,
        private Events $events,
    ) {
    }

    public function __invoke(Disconnect $command): void
    {
        $connection = $this->connections->get(new MemberId($command->memberId), Provider::from($command->provider));
        $connection->disconnect($this->providerAccess, $command->executedAt);
        $this->connections->remove($connection);
        $this->publishWhatHappenedTo($connection);
    }
}
