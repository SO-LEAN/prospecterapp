<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Port;

use Prospecter\Core\Booking\Domain\Model\PlannedExchange;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Port: the exchanges a host still has planned. The adapter translates them from Prospecting, the context that keeps
 * them, through its published service `ConsultPlannedExchangesService` (ADR 0089, 0133, 0153). It passes on each one's
 * duration, given or not: Booking decides how long an exchange keeps its host busy.
 */
interface HostAgendas
{
    /**
     * @return list<PlannedExchange> the exchanges that start at `from` or later
     */
    public function plannedExchangesOf(AccountId $accountId, MemberId $host, \DateTimeImmutable $from): array;
}
