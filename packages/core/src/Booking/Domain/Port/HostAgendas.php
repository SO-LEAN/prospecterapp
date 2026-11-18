<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Port;

use Prospecter\Core\Booking\Domain\Model\BusyPeriod;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: when a host is already taken, by the exchanges they still have planned, translated from the context that
 * keeps them (Prospecting), through its published service `ConsultPlannedExchangesService` (ADR 0089, 0133, 0153).
 */
interface HostAgendas
{
    /**
     * @return list<BusyPeriod> those starting from `from` on
     */
    public function busyPeriodsOf(AccountId $accountId, MemberId $host, \DateTimeImmutable $from): array;
}
