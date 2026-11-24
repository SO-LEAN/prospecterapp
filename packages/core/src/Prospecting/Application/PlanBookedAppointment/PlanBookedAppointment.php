<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\PlanBookedAppointment;

final readonly class PlanBookedAppointment
{
    /**
     * @param string $contactId the booker, as PersonalData identified them
     */
    public function __construct(
        public string $appointmentId,
        public string $hostId,
        public string $contactId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
