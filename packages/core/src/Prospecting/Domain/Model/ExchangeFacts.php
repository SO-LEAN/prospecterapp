<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\ChannelNotPlanned;
use Prospecter\Core\Prospecting\Domain\Exception\MissedOnlyForACall;

/**
 * What happened between the prospector and the contact in the real world, or what they agreed upon: the moment,
 * and how long it lasts, of a planned exchange; the subject of an email, if any, is in the text.
 */
final readonly class ExchangeFacts
{
    public ?string $text;

    /**
     * @throws MissedOnlyForACall
     */
    public function __construct(
        public Channel $channel,
        public Direction $direction,
        public \DateTimeImmutable $occurredAt,
        ?string $text = null,
        public bool $missed = false,
        public ?Duration $duration = null,
    ) {
        if ($missed && Channel::Call !== $channel) {
            throw MissedOnlyForACall::because('Only a call may be missed', 'missed', context: ['channel' => $channel->value]);
        }

        $this->text = null === $text || '' === trim($text) ? null : $text;
    }

    /**
     * Agreed upon with the contact, at the initiative of the prospector; the text is the agenda.
     *
     * @throws ChannelNotPlanned
     */
    public static function planned(Channel $channel, \DateTimeImmutable $at, Duration $duration, ?string $agenda): self
    {
        if (!$channel->mayBePlanned()) {
            throw ChannelNotPlanned::because('Only a call, a meeting or a video call may be planned', 'channel', context: ['channel' => $channel->value]);
        }

        return new self($channel, Direction::Outbound, $at, $agenda, duration: $duration);
    }

    public function withText(?string $text): self
    {
        return new self($this->channel, $this->direction, $this->occurredAt, $text, $this->missed, $this->duration);
    }
}
