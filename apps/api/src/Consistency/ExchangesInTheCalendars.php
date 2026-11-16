<?php

namespace App\Consistency;

use App\Prospecting\Persistence\ExchangeDocument;
use App\Shared\Persistence\Documents;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Symfony\Component\Clock\ClockInterface;

/**
 * What the pushes into the calendars should have written, read in the tables (ADR 0133): each exchange still planned
 * and to come of a member connected to a provider is synced in their calendar there.
 */
final readonly class ExchangesInTheCalendars
{
    public function __construct(private ContextTables $tables, private ClockInterface $clock)
    {
    }

    /**
     * @param list<string> $accountIds
     *
     * @return list<Discrepancy>
     */
    public function discrepanciesAmong(array $accountIds): array
    {
        return array_merge([], ...array_map($this->notSyncedIn(...), $accountIds));
    }

    /**
     * @return list<Discrepancy>
     */
    private function notSyncedIn(string $accountId): array
    {
        $connected = $this->tables->connectedMembersOf($accountId);
        if ([] === $connected) {
            return [];
        }

        $discrepancies = [];
        foreach ($this->tables->exchangesOf($accountId) as $exchange) {
            $provider = $connected[$exchange['createdBy']] ?? null;

            if (null !== $provider && $this->isToCome($exchange) && !$this->tables->isSynced($exchange['id'], $provider)) {
                $discrepancies[] = new Discrepancy($accountId, $exchange['id'], 'exchange to come not synced in the calendar of its holder at '.$provider, Correction::PushTheExchangeAgain);
            }
        }

        return $discrepancies;
    }

    /**
     * @param array<string, mixed> $exchange
     */
    private function isToCome(array $exchange): bool
    {
        return ExchangeStatus::Planned === ExchangeDocument::statusOf($exchange) && Documents::momentOf($exchange['facts']['occurredAt']) >= $this->clock->now();
    }
}
