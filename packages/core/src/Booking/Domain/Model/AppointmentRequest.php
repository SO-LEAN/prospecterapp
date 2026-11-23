<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * What a booker asks a booking page for: a slot, a channel, who they are and the subject. The client gives two ids: the
 * appointment's id, and the id the booker gets when nobody in the account matches them.
 */
final readonly class AppointmentRequest
{
    public function __construct(
        public AppointmentId $id,
        public BookerId $bookerId,
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
