<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Contract\Service\ConsultAppointments;

interface ConsultAppointmentsOutput
{
    /**
     * @param list<AppointmentResponse> $appointments the requested appointments that Booking still keeps. An
     *                                                unknown appointment, or one forgotten after its slot ended, is absent
     */
    public function present(array $appointments): void;
}
