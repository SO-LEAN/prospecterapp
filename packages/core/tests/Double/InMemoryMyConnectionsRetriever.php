<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\Connectors\Application\Port\MyConnectionsRetriever;
use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\ConnectionState;
use Prospecter\Core\Connectors\Domain\Model\MailImport;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\SyncState;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads the connections that the in-memory repository saved, as the adapter reads their items. It also reads their
 * member's entries.
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
            static fn (CalendarEntry $entry): bool => StateOf::the($entry)->object('startsAt', \DateTimeImmutable::class) >= $from,
        );

        return new MyConnection($state->object('provider', Provider::class)->value, $state->object('state', ConnectionState::class)->value, $state->object('externalAccount', Email::class)->value, $state->object('connectedAt', \DateTimeImmutable::class), self::countIn($upcoming, SyncState::Synced), self::countIn($upcoming, SyncState::Failed), $state->object('mailImport', MailImport::class)->value, $state->objectOrNull('lastNotifiedAt', \DateTimeImmutable::class));
    }

    /**
     * @param array<CalendarEntry> $entries
     */
    private static function countIn(array $entries, SyncState $state): int
    {
        return count(array_filter($entries, static fn (CalendarEntry $entry): bool => StateOf::the($entry)->object('state', SyncState::class) === $state));
    }
}
