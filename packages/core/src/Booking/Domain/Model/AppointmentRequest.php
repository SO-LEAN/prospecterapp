<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * What a booker asks of a booking page: a slot, by a channel, saying who they are and what about; the identities
 * given by the client, that of the appointment and the one the booker gets should nobody of the account be them.
 */
final readonly class AppointmentRequest
{
    public function __construct(
        public AppointmentId $id,
        public PersonId $bookerId,
        public \DateTimeImmutable $slot,
        public Channel $channel,
        public Booker $booker,
        public ?string $subject,
    ) {
    }

    #[\NoDiscard]
    public function onTheClockOf(\DateTimeZone $clock): self
    {
        return clone ($this, ['slot' => $this->slot->setTimezone($clock)]);
    }
}
