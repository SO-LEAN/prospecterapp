<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Service;

use Prospecter\Core\Booking\Domain\Model\Appointment;
use Prospecter\Core\Booking\Domain\Model\BusyPeriod;
use Prospecter\Core\Booking\Domain\Port\HostAgendas;
use Prospecter\Core\Booking\Domain\Repository\AppointmentRepository;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * When a host is already busy from a given moment: the exchanges they still have planned, and the appointments booked
 * through their link that Prospecting has not planned yet (ADR 0153, 0164).
 */
final readonly class BusyPeriods
{
    /**
     * An exchange planned the day before may run into the first slots. Prospecting plans no exchange longer than this.
     */
    private const string LONGEST_PLANNED_EXCHANGE_BEFORE = '-8 hours';

    public function __construct(
        private HostAgendas $hostAgendas,
        private AppointmentRepository $appointments,
    ) {
    }

    /**
     * @return list<BusyPeriod>
     */
    public function of(AccountId $accountId, MemberId $host, \DateTimeImmutable $at): array
    {
        return [
            ...$this->hostAgendas->busyPeriodsOf($accountId, $host, $at->modify(self::LONGEST_PLANNED_EXCHANGE_BEFORE)),
            ...array_map(static fn (Appointment $appointment): BusyPeriod => $appointment->busyPeriod(), $this->appointments->toComeOf($host, $at)),
        ];
    }
}
