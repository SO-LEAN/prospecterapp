<?php

declare(strict_types=1);

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Connectors\Application\Connection\MyConnection;
use Prospecter\Core\Connectors\Application\Port\MyConnectionsRetriever;
use Prospecter\Core\Connectors\Domain\Model\MailImport;
use Prospecter\Core\Connectors\Domain\Model\SyncState;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads a member's connections in the `MEMBER#<member>` items that `DynamoConnectionRepository` writes. It does not
 * restore them (ADR 0088), and it leaves their tokens unread. It also counts the entries of the member's upcoming
 * exchanges by state. They are listed under the member in `GSI1` (`ENTRIES#<member>`, ADR 0133).
 */
final readonly class DynamoMyConnectionsRetriever implements MyConnectionsRetriever
{
    public function __construct(private Table $connectorsTable)
    {
    }

    public function of(MemberId $me, \DateTimeImmutable $from): array
    {
        $upcoming = Typed::documents(array_column($this->connectorsTable->queryAfter(ConnectorsKeys::entriesOf($me->value), Table::BY_GSI1, Documents::moment($from)), 'entry'));

        return array_map(static fn (array $connection): MyConnection => self::connectionOf($connection, $upcoming), Typed::documents(array_column($this->connectorsTable->query(ConnectorsKeys::member($me->value)), 'connection')));
    }

    /**
     * @param array<string, mixed>       $connection
     * @param list<array<string, mixed>> $upcoming   the member's upcoming entries
     */
    private static function connectionOf(array $connection, array $upcoming): MyConnection
    {
        $provider = Typed::string($connection['provider']);

        return new MyConnection(
            $provider,
            Typed::string($connection['state']),
            Typed::string($connection['externalAccount']),
            Documents::momentOf(Typed::string($connection['connectedAt'])),
            self::count($upcoming, $provider, SyncState::Synced),
            self::count($upcoming, $provider, SyncState::Failed),
            Typed::stringOrNull($connection['mailImport'] ?? null) ?? MailImport::Off->value,
            Documents::momentOfOrNone(Typed::stringOrNull($connection['lastNotifiedAt'] ?? null)),
        );
    }

    /**
     * @param list<array<string, mixed>> $entries
     */
    private static function count(array $entries, string $provider, SyncState $state): int
    {
        return count(array_filter($entries, static fn (array $entry): bool => $provider === $entry['provider'] && $state->value === $entry['state']));
    }
}
