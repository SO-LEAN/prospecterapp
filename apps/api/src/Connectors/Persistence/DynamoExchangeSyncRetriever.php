<?php

declare(strict_types=1);

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Connectors\Application\ExchangeSync;
use Prospecter\Core\Connectors\Application\Port\ExchangeSyncRetriever;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\SyncState;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads where the push of an exchange stands in the item `EXCHANGE#<exchange>` that `DynamoCalendarEntryRepository`
 * writes, without restoring it (ADR 0088, 0133); an entry written before ADR 0133 synced.
 */
final readonly class DynamoExchangeSyncRetriever implements ExchangeSyncRetriever
{
    public function __construct(private Table $connectorsTable)
    {
    }

    public function find(ExchangeId $exchange, MemberId $member): ?ExchangeSync
    {
        $entry = $this->connectorsTable->query(ConnectorsKeys::exchange($exchange->value))[0]['entry'] ?? null;
        if (null === $entry || $member->value !== $entry['member']) {
            return null;
        }

        $since = $entry['since'] ?? null;

        return new ExchangeSync($entry['provider'], $entry['state'] ?? SyncState::Synced->value, null === $since ? null : Documents::momentOf($since), $entry['reason'] ?? null);
    }
}
