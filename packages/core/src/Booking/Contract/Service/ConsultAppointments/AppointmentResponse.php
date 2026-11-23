<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Contract\Service\ConsultAppointments;

/**
 * An appointment in the published language (ADR 0045, 0164): its host, its start on the host's clock and its length,
 * its channel (`call`, `videoCall`), the booker as they described themselves (their phone in E.164, their e-mail in
 * lower case), and the subject.
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
