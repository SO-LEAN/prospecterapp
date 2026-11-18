<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\Connectors\Application\Port\MyConnectionsRetriever;
use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\SyncState;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the connections the in-memory repository saved, as the adapter reads their items, and the entries of their
 * member.
 */
final readonly class InMemoryMyConnectionsRetriever implements MyConnectionsRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function of(MemberId $me, \DateTimeImmutable $from): array
    {
        return array_map(fn (Connection $connection): MyConnection => $this->asMine($connection, $from), $this->database->connectionsOf($me->value));
    }

    private function asMine(Connection $connection, \DateTimeImmutable $from): MyConnection
    {
        $state = StateOf::the($connection);
        $upcoming = array_filter(
            $this->database->calendarEntriesOfMember($connection->member()->value),
            static fn (CalendarEntry $entry): bool => StateOf::the($entry)['provider'] === $state['provider'] && StateOf::the($entry)['startsAt'] >= $from,
        );

        return new MyConnection($state['provider']->value, $state['state']->value, $state['externalAccount']->value, $state['connectedAt'], self::countIn($upcoming, SyncState::Synced), self::countIn($upcoming, SyncState::Failed), $state['mailImport']->value);
    }

    /**
     * @param array<CalendarEntry> $entries
     */
    private static function countIn(array $entries, SyncState $state): int
    {
        return count(array_filter($entries, static fn (CalendarEntry $entry): bool => StateOf::the($entry)['state'] === $state));
    }
}
