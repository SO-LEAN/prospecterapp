<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Email;

/**
 * When a host is already taken from a moment on, and the appointments booked by their link still to come.
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
