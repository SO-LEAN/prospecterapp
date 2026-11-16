<?php

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\Connectors\Application\Port\MyConnectionsRetriever;
use Prospecter\Core\Connectors\Domain\Model\SyncState;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the connections of a member in the items `MEMBER#<member>` that `DynamoConnectionRepository` writes,
 * without restoring them (ADR 0088), their tokens left where they are; and counts the entries of their exchanges to
 * come by their state, listed under the member in `GSI1` (`ENTRIES#<member>`, ADR 0133).
 */
final readonly class DynamoMyConnectionsRetriever implements MyConnectionsRetriever
{
    public function __construct(private Table $connectorsTable)
    {
    }

    public function of(MemberId $me, \DateTimeImmutable $from): array
    {
        $upcoming = array_column($this->connectorsTable->queryAfter(ConnectorsKeys::entriesOf($me->value), Table::BY_GSI1, Documents::moment($from)), 'entry');

        return array_map(static fn (array $item): MyConnection => new MyConnection(
            $item['connection']['provider'],
            $item['connection']['state'],
            $item['connection']['externalAccount'],
            Documents::momentOf($item['connection']['connectedAt']),
            self::count($upcoming, $item['connection']['provider'], SyncState::Synced),
            self::count($upcoming, $item['connection']['provider'], SyncState::Failed),
        ), $this->connectorsTable->query(ConnectorsKeys::member($me->value)));
    }

    /**
     * @param list<array<string, mixed>> $entries
     */
    private static function count(array $entries, string $provider, SyncState $state): int
    {
        return count(array_filter($entries, static fn (array $entry): bool => $provider === $entry['provider'] && $state->value === $entry['state']));
    }
}
