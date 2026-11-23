<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * What a booker booked with a host: when, on the host's clock, for how long, through which channel, and what about.
 */
final readonly class BookedAppointment
{
    public function __construct(
        public \DateTimeImmutable $startsAt,
        public Duration $duration,
        public Channel $channel,
        public ?string $subject,
    ) {
    }

    /**
     * A video call requests its link from the host's calendar. The calendar's invitation gives the link to the
     * booker.
     */
    public function facts(): ExchangeFacts
    {
        $facts = ExchangeFacts::planned($this->channel, $this->startsAt, $this->duration, $this->subject);

        return Channel::VideoCall === $this->channel ? $facts->askingALink() : $facts;
    }
}
