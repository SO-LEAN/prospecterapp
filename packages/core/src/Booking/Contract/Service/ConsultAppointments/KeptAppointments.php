<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Contract\Service\ConsultAppointments;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptAppointments implements ConsultAppointmentsOutput
{
    /**
     * @var list<AppointmentResponse>
     */
    public private(set) array $appointments = [];

    /**
     * @param list<AppointmentResponse> $appointments
     */
    public function present(array $appointments): void
    {
        $this->appointments = $appointments;
    }
}
