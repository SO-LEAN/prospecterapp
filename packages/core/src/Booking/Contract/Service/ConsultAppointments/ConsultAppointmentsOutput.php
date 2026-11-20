<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Contract\Service\ConsultAppointments;

interface ConsultAppointmentsOutput
{
    /**
     * @param list<AppointmentResponse> $appointments those, among the appointments asked, Booking still keeps; one
     *                                                unknown, or forgotten once its slot ended, is absent
     */
    public function present(array $appointments): void;
}
