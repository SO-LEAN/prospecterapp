<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * What a booker booked with a host: when, on the clock of the host, for how long, by which channel, and what about.
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
     * A video call asks its link of the calendar of the host, whose invitation gives it to the booker.
     */
    public function facts(): ExchangeFacts
    {
        return ExchangeFacts::planned($this->channel, $this->startsAt, $this->duration, $this->subject, linkRequested: Channel::VideoCall === $this->channel);
    }
}
