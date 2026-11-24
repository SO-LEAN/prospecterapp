<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\PersonalData\Contract\Event\BookerIdentified;
use Prospecter\Core\Prospecting\Application\PlanBookedAppointment\PlanBookedAppointment;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When PersonalData identifies who booked a host's slot, the host plans the exchange the appointment becomes
 * (ADR 0164).
 */
final readonly class PlanBookedAppointmentPolicy implements Policy
{
    public function __invoke(BookerIdentified $event): PlanBookedAppointment
    {
        return new PlanBookedAppointment(
            appointmentId: $event->appointmentId,
            hostId: $event->hostId,
            contactId: $event->personId,
            executedAt: $event->occurredAt,
        );
    }
}
