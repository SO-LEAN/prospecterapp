<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Contract\Service\ConsultAppointments;

/**
 * The published language of an appointment (ADR 0045, 0164): its host, when it starts on the clock of the host and
 * how long it lasts, its channel (`call`, `videoCall`), who booked it as they said it (their phone in E.164, their
 * e-mail in lower case) and what about.
 */
final readonly class AppointmentResponse
{
    public function __construct(
        public string $appointmentId,
        public string $hostId,
        public \DateTimeImmutable $startsAt,
        public int $durationInMinutes,
        public string $channel,
        public string $firstName,
        public string $lastName,
        public string $phone,
        public string $email,
        public ?string $subject,
    ) {
    }
}
