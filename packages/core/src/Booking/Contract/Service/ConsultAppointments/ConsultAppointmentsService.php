<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Contract\Service\ConsultAppointments;

/**
 * The service Booking offers the other contexts (ADR 0045, 0089): it refuses nothing, what is unknown is absent.
 */
interface ConsultAppointmentsService
{
    public function __invoke(ConsultAppointments $query, ConsultAppointmentsOutput $output): void;
}
