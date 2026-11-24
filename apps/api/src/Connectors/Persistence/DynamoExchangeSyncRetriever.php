<?php

declare(strict_types=1);

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Connectors\Application\Calendar\ExchangeSync;
use Prospecter\Core\Connectors\Application\Port\ExchangeSyncRetriever;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\SyncState;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads an exchange's push state in the `EXCHANGE#<exchange>` item that `DynamoCalendarEntryRepository` writes,
 * without restoring the entry (ADR 0088, 0133). An entry written before ADR 0133 reads as synced.
 */
final readonly class DynamoExchangeSyncRetriever implements ExchangeSyncRetriever
{
    public function __construct(private Table $connectorsTable)
    {
    }

    public function find(ExchangeId $exchange, MemberId $member): ?ExchangeSync
    {
        $item = $this->connectorsTable->query(ConnectorsKeys::exchange($exchange->value))[0] ?? null;
        $entry = null === $item ? null : Typed::document($item['entry']);
        if (null === $entry || $member->value !== $entry['member']) {
            return null;
        }

        $since = Typed::stringOrNull($entry['since'] ?? null);

        return new ExchangeSync(Typed::string($entry['provider']), Typed::stringOrNull($entry['state'] ?? null) ?? SyncState::Synced->value, null === $since ? null : Documents::momentOf($since), Typed::stringOrNull($entry['reason'] ?? null));
    }
}
