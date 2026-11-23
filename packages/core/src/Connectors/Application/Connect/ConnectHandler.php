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
use Prospecter\Core\Connectors\Domain\Model\ProviderClient;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\Connectors\Domain\Repository\AuthorizationRepository;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ConnectHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private AuthorizationRepository $authorizations,
        private ConnectionRepository $connections,
        private ProviderClientRepository $clients,
        private ProviderAccess $providerAccess,
        private ProviderMailbox $mailbox,
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
        $grant = $this->providerAccess->exchange($client->credentials(), $command->code, $authorization->codeVerifier);
        $connection = $this->connectionGranted($me, $client->credentials(), $grant, $command->executedAt);
        $this->importTheMailWhenGranted($connection, $client, $authorization, $grant, $command->executedAt);
        $this->connections->save($connection);
        $this->publishWhatHappenedTo($connection);
    }

    private function awaitedAuthorization(string $state, MemberId $me, \DateTimeImmutable $at): PendingAuthorization
    {
        $authorization = $this->authorizations->find($state);

        return null !== $authorization && $authorization->awaits($me, $at) ? $authorization : throw UnknownAuthorization::forTheAnswer();
    }

    private function clientOf(Member $me, Provider $provider): ProviderClient
    {
        return $this->clients->find($me->accountId, $provider) ?? throw ProviderUnavailable::withoutClient($provider);
    }

    private function connectionGranted(Member $me, ClientCredentials $client, Grant $grant, \DateTimeImmutable $at): Connection
    {
        $connection = $this->connections->find($me->id, $client->provider);

        if (null === $connection) {
            return Connection::establish(new Gesture($me->accountId, $me->id, $at), $client, $grant);
        }

        $connection->renew($grant, $client, $this->providerAccess, $at);

        return $connection;
    }

    /**
     * The e-mail import starts once the member granted the mailbox access that they asked for, while the account still
     * has its topic. When the member unticked the mailbox on the consent screen, nothing is imported. The calendar is
     * connected all the same.
     */
    private function importTheMailWhenGranted(Connection $connection, ProviderClient $client, PendingAuthorization $authorization, Grant $grant, \DateTimeImmutable $at): void
    {
        $notifications = $client->mailboxNotifications();

        if (null !== $notifications && $authorization->asks(Scope::Mailbox) && $grant->grants(Scope::Mailbox)) {
            $connection->importMail($client->credentials(), $notifications, $this->mailbox, $at);
        }
    }
}
