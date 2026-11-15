<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\HeldExchange;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Port: the exchanges as they are now, translated from the context that keeps them (Prospecting), through its
 * published service `ConsultHeldExchangesService` (ADR 0089, 0127).
 */
interface HeldExchanges
{
    /**
     * None once the exchange is erased.
     */
    public function find(AccountId $accountId, ExchangeId $id): ?HeldExchange;
}
