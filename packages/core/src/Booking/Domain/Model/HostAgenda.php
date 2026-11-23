<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Contact\Email;

/**
 * When a host is already busy from a given moment, and the upcoming appointments that bookers made through the host's
 * link.
 */
final readonly class HostAgenda
{
    /**
     * @param list<BusyPeriod>  $busyPeriods
     * @param list<Appointment> $appointments
     */
    public function __construct(
        public array $busyPeriods,
        public array $appointments,
    ) {
    }

    public function appointmentOf(Email $booker): ?Appointment
    {
        return array_find($this->appointments, static fn (Appointment $appointment): bool => $appointment->isBookedBy($booker));
    }
}
