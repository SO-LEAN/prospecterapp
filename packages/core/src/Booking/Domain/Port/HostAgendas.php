<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Port;

use Prospecter\Core\Booking\Domain\Model\BusyPeriod;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Port: when a host is already busy with the exchanges they still have planned. The adapter translates them from
 * Prospecting, the context that keeps them, through its published service `ConsultPlannedExchangesService` (ADR 0089,
 * 0133, 0153).
 */
interface HostAgendas
{
    /**
     * @return list<BusyPeriod> the periods that start at `from` or later
     */
    public function busyPeriodsOf(AccountId $accountId, MemberId $host, \DateTimeImmutable $from): array;
}
