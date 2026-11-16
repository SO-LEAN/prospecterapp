<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\HeldExchange;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: the exchanges as they are now, translated from the context that keeps them (Prospecting), through its
 * published services `ConsultHeldExchangesService` (ADR 0089, 0127) and `ConsultPlannedExchangesService` (ADR 0133).
 */
interface HeldExchanges
{
    /**
     * None once the exchange is erased.
     */
    public function find(AccountId $accountId, ExchangeId $id): ?HeldExchange;

    /**
     * Those the holder still has planned from `from` on, the soonest first.
     *
     * @return list<HeldExchange>
     */
    public function plannedBy(AccountId $accountId, MemberId $holder, \DateTimeImmutable $from): array;
}
