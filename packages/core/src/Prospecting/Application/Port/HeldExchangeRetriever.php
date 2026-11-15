<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\ConsultHeldExchanges\HeldExchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Reads the exchanges as their holders hold them, without restoring them (ADR 0088).
 */
interface HeldExchangeRetriever
{
    /**
     * Those of the account among these ids, in no particular order.
     *
     * @param list<ExchangeId> $ids
     *
     * @return list<HeldExchange>
     */
    public function ofIds(AccountId $accountId, array $ids): array;
}
