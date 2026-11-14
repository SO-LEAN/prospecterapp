<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Connect;

use Prospecter\Core\Connectors\Domain\Exception\UnknownAuthorization;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\PendingAuthorization;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\Connectors\Domain\Repository\AuthorizationRepository;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConnectHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private AuthorizationRepository $authorizations,
        private ConnectionRepository $connections,
        private ProviderAccess $providerAccess,
        private Members $members,
        private Events $events,
    ) {
    }

    public function __invoke(Connect $command): void
    {
        $me = new MemberId($command->memberId);
        $authorization = $this->awaitedAuthorization($command->state, $me, $command->executedAt);
        $this->authorizations->forget($authorization);
        $grant = $this->providerAccess->exchange($authorization->provider, $command->code, $authorization->codeVerifier);
        $connection = $this->connectionGranted($me, $authorization->provider, $grant, $command->executedAt);
        $this->connections->save($connection);
        $this->publishWhatHappenedTo($connection);
    }

    private function awaitedAuthorization(string $state, MemberId $me, \DateTimeImmutable $at): PendingAuthorization
    {
        $authorization = $this->authorizations->find($state);

        return null !== $authorization && $authorization->awaits($me, $at) ? $authorization : throw UnknownAuthorization::forTheAnswer();
    }

    private function connectionGranted(MemberId $me, Provider $provider, Grant $grant, \DateTimeImmutable $at): Connection
    {
        $connection = $this->connections->find($me, $provider);

        if (null === $connection) {
            return Connection::establish($me, $this->members->accountOf($me), $provider, $grant, $at);
        }

        $connection->renew($grant, $this->providerAccess, $at);

        return $connection;
    }
}
