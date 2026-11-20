<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Contract\Service\ConsultAppointments;

/**
 * The appointments booked by these identities, while they hold their slot (ADR 0089, 0164): asked about a set, a
 * single appointment being a set of one.
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
