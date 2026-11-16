<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\ConsultHeldExchanges\HeldExchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

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

    /**
     * Those of the account the holder still has planned from `from` on, the soonest first.
     *
     * @return list<HeldExchange>
     */
    public function plannedBy(AccountId $accountId, MemberId $holder, \DateTimeImmutable $from): array;
}
