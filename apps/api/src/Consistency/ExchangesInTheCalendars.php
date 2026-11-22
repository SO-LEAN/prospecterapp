<?php

declare(strict_types=1);

namespace App\Consistency;

use App\Prospecting\Persistence\ExchangeDocument;
use App\Shared\Persistence\Documents;
use App\Shared\Typed;
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
        $connectedByAccount = $this->tables->connectedMembersByAccount();

        return array_merge([], ...array_map(fn (string $accountId): array => $this->notSyncedIn($accountId, $connectedByAccount[$accountId] ?? []), $accountIds));
    }

    /**
     * @param array<string, string> $connected the provider each member of the account is connected to, by member
     *
     * @return list<Discrepancy>
     */
    private function notSyncedIn(string $accountId, array $connected): array
    {
        if ([] === $connected) {
            return [];
        }

        $discrepancies = [];
        foreach ($this->tables->exchangesOf($accountId) as $exchange) {
            $provider = $connected[Typed::string($exchange['createdBy'])] ?? null;
            $id = Typed::string($exchange['id']);

            if (null !== $provider && $this->isToCome($exchange) && !$this->tables->isSynced($id, $provider)) {
                $discrepancies[] = new Discrepancy($accountId, $id, 'exchange to come not synced in the calendar of its holder at '.$provider, Correction::PushTheExchangeAgain);
            }
        }

        return $discrepancies;
    }

    /**
     * @param array<string, mixed> $exchange
     */
    private function isToCome(array $exchange): bool
    {
        return ExchangeDocument::isPlanned($exchange) && Documents::momentOf(Typed::string(Typed::document($exchange['facts'])['occurredAt'])) >= $this->clock->now();
    }
}
