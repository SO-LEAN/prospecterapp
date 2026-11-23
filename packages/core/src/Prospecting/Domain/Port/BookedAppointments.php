<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Port;

use Prospecter\Core\Prospecting\Domain\Model\BookedAppointment;

/**
 * Port: what a booker booked with a host through the host's public link. It is translated from Booking, the context
 * that keeps the appointments, through its published service `ConsultAppointmentsService` (ADR 0089, 0164).
 */
interface BookedAppointments
{
    /**
     * Returns null once the appointment is gone.
     */
    public function bookedAppointment(string $appointmentId): ?BookedAppointment;
}
