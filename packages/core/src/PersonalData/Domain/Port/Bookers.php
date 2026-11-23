<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Port;

use Prospecter\Core\PersonalData\Domain\Model\Booker;

/**
 * Port: who booked an appointment, as they described themselves. It translates from the context that keeps the
 * appointments (Booking), through its published service `ConsultAppointmentsService` (ADR 0089, 0164).
 */
interface Bookers
{
    /**
     * None once the appointment is gone.
     */
    public function bookerOf(string $appointmentId): ?Booker;
}
