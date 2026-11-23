<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0164): a booker booked one of this host's slots through
 * the public link. The other contexts read who the booker is and what they booked from `ConsultAppointmentsService`.
 * The booker takes the given id when nobody in the account matches them.
 */
final readonly class SlotBooked
{
    public function __construct(
        public string $appointmentId,
        public string $accountId,
        public string $hostId,
        public string $bookerId,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
