<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Repository;

use Prospecter\Core\Booking\Domain\Exception\SlotNotFree;
use Prospecter\Core\Booking\Domain\Model\Appointment;
use Prospecter\Core\Booking\Domain\Model\AppointmentId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

interface AppointmentRepository
{
    public function find(AppointmentId $id): ?Appointment;

    /**
     * The appointments of the host not ended at this moment, the soonest first.
     *
     * @return list<Appointment>
     */
    public function toComeOf(MemberId $host, \DateTimeImmutable $at): array;

    /**
     * @throws SlotNotFree when another appointment took its slot meanwhile
     */
    public function save(Appointment $appointment): void;
}
