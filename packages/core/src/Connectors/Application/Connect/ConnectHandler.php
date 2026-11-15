<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Connect;

use Prospecter\Core\Connectors\Domain\Exception\ProviderUnavailable;
use Prospecter\Core\Connectors\Domain\Exception\UnknownAuthorization;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\Member;
use Prospecter\Core\Connectors\Domain\Model\PendingAuthorization;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\Connectors\Domain\Repository\AuthorizationRepository;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
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
        private ProviderClientRepository $clients,
        private ProviderAccess $providerAccess,
        private Members $members,
        private Events $events,
    ) {
    }

    public function __invoke(Connect $command): void
    {
        $me = $this->members->get(new MemberId($command->memberId));
        $authorization = $this->awaitedAuthorization($command->state, $me->id, $command->executedAt);
        $this->authorizations->forget($authorization);
        $client = $this->clientOf($me, $authorization->provider);
        $grant = $this->providerAccess->exchange($client, $command->code, $authorization->codeVerifier);
        $connection = $this->connectionGranted($me, $client, $grant, $command->executedAt);
        $this->connections->save($connection);
        $this->publishWhatHappenedTo($connection);
    }

    private function awaitedAuthorization(string $state, MemberId $me, \DateTimeImmutable $at): PendingAuthorization
    {
        $authorization = $this->authorizations->find($state);

        return null !== $authorization && $authorization->awaits($me, $at) ? $authorization : throw UnknownAuthorization::forTheAnswer();
    }

    private function clientOf(Member $me, Provider $provider): ClientCredentials
    {
        return ($this->clients->find($me->accountId, $provider) ?? throw ProviderUnavailable::withoutClient($provider))->credentials();
    }

    private function connectionGranted(Member $me, ClientCredentials $client, Grant $grant, \DateTimeImmutable $at): Connection
    {
        $connection = $this->connections->find($me->id, $client->provider);

        if (null === $connection) {
            return Connection::establish($me->id, $me->accountId, $client, $grant, $at);
        }

        $connection->renew($grant, $client, $this->providerAccess, $at);

        return $connection;
    }
}
