<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0164): a booker booked a slot of this host by their
 * public link; who they are and what they booked are read from `ConsultAppointmentsService`, the identity given to
 * the booker serving should nobody of the account be them.
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
