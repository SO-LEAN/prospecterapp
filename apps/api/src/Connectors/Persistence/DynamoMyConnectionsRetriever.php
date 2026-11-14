<?php

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\Connectors\Application\Port\MyConnectionsRetriever;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the connections of a member in the items `MEMBER#<member>` that `DynamoConnectionRepository` writes,
 * without restoring them (ADR 0088), their tokens left where they are.
 */
final readonly class DynamoMyConnectionsRetriever implements MyConnectionsRetriever
{
    public function __construct(private Table $connectorsTable)
    {
    }

    public function of(MemberId $me): array
    {
        return array_map(static fn (array $item): MyConnection => new MyConnection(
            $item['connection']['provider'],
            $item['connection']['state'],
            $item['connection']['externalAccount'],
            Documents::momentOf($item['connection']['connectedAt']),
        ), $this->connectorsTable->query(ConnectorsKeys::member($me->value)));
    }
}
