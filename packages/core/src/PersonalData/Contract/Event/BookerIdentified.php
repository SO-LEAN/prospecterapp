<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Event;

/**
 * Integration event published to the other contexts (ADR 0046, 0164): the contact who booked this appointment with
 * this host by their public link, known already or collected to be attached.
 */
final readonly class BookerIdentified
{
    public function __construct(
        public string $appointmentId,
        public string $accountId,
        public string $hostId,
        public string $personId,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
