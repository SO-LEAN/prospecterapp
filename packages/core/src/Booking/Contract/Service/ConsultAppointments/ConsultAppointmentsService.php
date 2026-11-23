<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Contract\Service\ConsultAppointments;

/**
 * The service that Booking offers to the other contexts (ADR 0045, 0089). It refuses nothing: an unknown appointment is
 * absent from the answer.
 */
interface ConsultAppointmentsService
{
    public function __invoke(ConsultAppointments $query, ConsultAppointmentsOutput $output): void;
}
