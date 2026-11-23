<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Contract\Service\ConsultAppointments;

/**
 * The appointments with these ids, while they hold their slot (ADR 0089, 0164). The query takes a set of ids. A single
 * appointment is a set of one.
 */
final readonly class ConsultAppointments
{
    /**
     * @param list<string> $appointmentIds
     */
    public function __construct(public array $appointmentIds)
    {
    }
}
