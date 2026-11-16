<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Exception\NoConnection;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class InMemoryConnectionRepository implements ConnectionRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(MemberId $member, Provider $provider): Connection
    {
        return $this->find($member, $provider) ?? throw NoConnection::to($provider);
    }

    public function find(MemberId $member, Provider $provider): ?Connection
    {
        return $this->database->connection($member->value, $provider->value);
    }

    public function of(MemberId $member): array
    {
        return $this->database->connectionsOf($member->value);
    }

    public function save(Connection $connection): void
    {
        $this->database->storeConnection($connection);
    }

    public function replace(Connection $connection): void
    {
        if (null === $this->find($connection->member(), $connection->provider())) {
            throw new \RuntimeException('The connection was forgotten meanwhile');
        }

        $this->save($connection);
    }

    public function remove(Connection $connection): void
    {
        $this->database->forgetConnection($connection);
    }
}
