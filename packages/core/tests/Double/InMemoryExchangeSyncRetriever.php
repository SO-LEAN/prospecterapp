<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Application\ExchangeSync;
use Prospecter\Core\Connectors\Application\Port\ExchangeSyncRetriever;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\SyncState;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads the entries that the in-memory repository saved, as the adapter reads their items.
 */
final readonly class InMemoryExchangeSyncRetriever implements ExchangeSyncRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function find(ExchangeId $exchange, MemberId $member): ?ExchangeSync
    {
        $entry = $this->database->calendarEntry($exchange->value);
        if (null === $entry || !$entry->member()->equals($member)) {
            return null;
        }

        $state = StateOf::the($entry);

        return new ExchangeSync($state->object('provider', Provider::class)->value, $state->object('state', SyncState::class)->value, $state->object('since', \DateTimeImmutable::class), $state->stringOrNull('reason'));
    }
}
