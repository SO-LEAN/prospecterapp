<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Port;

use Prospecter\Core\Prospecting\Domain\Model\BookedAppointment;

/**
 * Port: what a booker booked with a host by their public link, translated from the context that keeps the
 * appointments (Booking), through its published service `ConsultAppointmentsService` (ADR 0089, 0164).
 */
interface BookedAppointments
{
    /**
     * None once the appointment is gone.
     */
    public function bookedAppointment(string $appointmentId): ?BookedAppointment;
}
