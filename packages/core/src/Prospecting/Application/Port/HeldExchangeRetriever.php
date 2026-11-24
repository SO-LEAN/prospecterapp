<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\Agenda\ConsultHeldExchanges\HeldExchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads the exchanges as their holders hold them, without restoring them (ADR 0088).
 */
interface HeldExchangeRetriever
{
    /**
     * Returns the account's exchanges among these ids, in no particular order.
     *
     * @param list<ExchangeId> $ids
     *
     * @return list<HeldExchange>
     */
    public function ofIds(AccountId $accountId, array $ids): array;

    /**
     * Returns the account's exchanges that the holder still has planned at `from` or later, soonest first.
     *
     * @return list<HeldExchange>
     */
    public function plannedBy(AccountId $accountId, MemberId $holder, \DateTimeImmutable $from): array;
}
