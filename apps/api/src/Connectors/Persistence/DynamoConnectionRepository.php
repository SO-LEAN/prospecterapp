<?php

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use Prospecter\Core\Connectors\Domain\Exception\NoConnection;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\ConnectionState;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The connections in the table of Connectors (ADR 0120): `MEMBER#<member>` / `CONNECTION#<provider>`, the
 * connection as a document, its refresh token encrypted at rest by DynamoDB only.
 */
final readonly class DynamoConnectionRepository implements ConnectionRepository
{
    public function __construct(private Table $connectorsTable)
    {
    }

    public function get(MemberId $member, Provider $provider): Connection
    {
        return $this->find($member, $provider) ?? throw NoConnection::to($provider);
    }

    public function find(MemberId $member, Provider $provider): ?Connection
    {
        $item = $this->connectorsTable->item(ConnectorsKeys::member($member->value), ConnectorsKeys::connection($provider->value));

        return null === $item ? null : self::restore($item['connection']);
    }

    public function of(MemberId $member): array
    {
        return array_map(static fn (array $item): Connection => self::restore($item['connection']), $this->connectorsTable->query(ConnectorsKeys::member($member->value)));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function restore(array $document): Connection
    {
        return Hydrator::restore(Connection::class, [
            'member' => new MemberId($document['member']),
            'accountId' => new AccountId($document['accountId']),
            'provider' => Provider::from($document['provider']),
            'externalAccount' => new Email($document['externalAccount']),
            'refreshToken' => new RefreshToken($document['refreshToken']),
            'state' => ConnectionState::from($document['state']),
            'connectedAt' => Documents::momentOf($document['connectedAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }

    public function save(Connection $connection): void
    {
        $state = Hydrator::stateOf($connection);

        $this->connectorsTable->transact([$this->connectorsTable->putting([
            'PK' => ConnectorsKeys::member($connection->member()->value),
            'SK' => ConnectorsKeys::connection($connection->provider()->value),
            'connection' => [
                'member' => (string) $state['member'],
                'accountId' => (string) $state['accountId'],
                'provider' => $state['provider']->value,
                'externalAccount' => (string) $state['externalAccount'],
                'refreshToken' => (string) $state['refreshToken'],
                'state' => $state['state']->value,
                'connectedAt' => Documents::moment($state['connectedAt']),
                'modifiedAt' => Documents::moment($state['modifiedAt']),
            ],
        ])]);
    }

    public function remove(Connection $connection): void
    {
        $this->connectorsTable->transact([$this->connectorsTable->deleting(ConnectorsKeys::member($connection->member()->value), ConnectorsKeys::connection($connection->provider()->value))]);
    }
}
