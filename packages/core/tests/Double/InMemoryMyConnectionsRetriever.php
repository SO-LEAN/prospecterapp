<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\Connectors\Application\Port\MyConnectionsRetriever;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the connections the in-memory repository saved, as the adapter reads their items.
 */
final readonly class InMemoryMyConnectionsRetriever implements MyConnectionsRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function of(MemberId $me): array
    {
        return array_map(self::asMine(...), $this->database->connectionsOf($me->value));
    }

    private static function asMine(Connection $connection): MyConnection
    {
        $state = StateOf::the($connection);

        return new MyConnection($state['provider']->value, $state['state']->value, $state['externalAccount']->value, $state['connectedAt']);
    }
}
