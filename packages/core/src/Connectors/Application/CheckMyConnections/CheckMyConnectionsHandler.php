<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\CheckMyConnections;

use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class CheckMyConnectionsHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ConnectionRepository $connections,
        private ProviderClientRepository $clients,
        private ProviderAccess $providerAccess,
        private Members $members,
        private Events $events,
    ) {
    }

    public function __invoke(CheckMyConnections $command): void
    {
        $me = new MemberId($command->memberId);
        $myAccount = $this->members->get($me)->accountId;

        foreach ($this->connections->of($me) as $connection) {
            $this->check($connection, $myAccount, $command->executedAt);
        }
    }

    /**
     * Nothing can be asked of a provider when the account has no client there. Then the connection stays as it was
     * last seen.
     */
    private function check(Connection $connection, AccountId $myAccount, \DateTimeImmutable $at): void
    {
        $client = $this->clients->find($myAccount, $connection->provider());
        if (null === $client) {
            return;
        }

        $connection->check($client->credentials(), $this->providerAccess, $at);
        $this->connections->save($connection);
        $this->publishWhatHappenedTo($connection);
    }
}
