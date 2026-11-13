<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\ChannelNotPlanned;
use Prospecter\Core\Prospecting\Domain\Exception\LinkOnlyForAVideoCall;
use Prospecter\Core\Prospecting\Domain\Exception\PlaceOnlyForAMeeting;

/**
 * What happened between the prospector and the contact in the real world, or what they agreed upon: the moment,
 * and how long it lasts, of a planned exchange; where a meeting is held, written freely, and the link a video
 * call is joined by; the subject of an email, if any, is in the text.
 */
final readonly class ExchangeFacts
{
    public ?string $text;
    public ?string $place;

    /**
     * @throws PlaceOnlyForAMeeting|LinkOnlyForAVideoCall
     */
    public function __construct(
        public Channel $channel,
        public Direction $direction,
        public \DateTimeImmutable $occurredAt,
        ?string $text = null,
        public ?Duration $duration = null,
        ?string $place = null,
        public ?VideoCallLink $link = null,
    ) {
        $this->text = self::orNone($text);
        $this->place = self::orNone($place);
        self::ensureTheVenueFits($channel, $this->place, $link);
    }

    private static function orNone(?string $written): ?string
    {
        return null === $written || '' === trim($written) ? null : $written;
    }

    /**
     * @throws PlaceOnlyForAMeeting|LinkOnlyForAVideoCall
     */
    private static function ensureTheVenueFits(Channel $channel, ?string $place, ?VideoCallLink $link): void
    {
        if (null !== $place && !$channel->isHeldSomewhere()) {
            throw PlaceOnlyForAMeeting::because('Only a meeting has a place', 'place', context: ['channel' => $channel->value]);
        }

        if (null !== $link && !$channel->isJoinedByALink()) {
            throw LinkOnlyForAVideoCall::because('Only a video call has a link', 'link', context: ['channel' => $channel->value]);
        }
    }

    /**
     * Agreed upon with the contact, at the initiative of the prospector; the text is the agenda.
     *
     * @throws ChannelNotPlanned|PlaceOnlyForAMeeting|LinkOnlyForAVideoCall
     */
    public static function planned(Channel $channel, \DateTimeImmutable $at, Duration $duration, ?string $agenda, ?string $place = null, ?VideoCallLink $link = null): self
    {
        if (!$channel->mayBePlanned()) {
            throw ChannelNotPlanned::because('Only a call, a meeting or a video call may be planned', 'channel', context: ['channel' => $channel->value]);
        }

        return new self($channel, Direction::Outbound, $at, $agenda, $duration, $place, $link);
    }

    public function withText(?string $text): self
    {
        return new self($this->channel, $this->direction, $this->occurredAt, $text, $this->duration, $this->place, $this->link);
    }

    /**
     * As it took place, at the moment it did, with what was said, where it was held.
     */
    public function happened(\DateTimeImmutable $at, ?string $report): self
    {
        return new self($this->channel, $this->direction, $at, $report, $this->duration, $this->place, $this->link);
    }

    public function movedTo(\DateTimeImmutable $at): self
    {
        return new self($this->channel, $this->direction, $at, $this->text, $this->duration, $this->place, $this->link);
    }

    /**
     * @throws PlaceOnlyForAMeeting|LinkOnlyForAVideoCall
     */
    public function withVenue(?string $place, ?VideoCallLink $link): self
    {
        return new self($this->channel, $this->direction, $this->occurredAt, $this->text, $this->duration, $place, $link);
    }

    /**
     * Corrected as a whole, the facts keep where the exchange was held, or its link, while their channel still has
     * one.
     */
    public function keepingTheVenueOf(self $former): self
    {
        return new self(
            $this->channel,
            $this->direction,
            $this->occurredAt,
            $this->text,
            $this->duration,
            $this->channel->isHeldSomewhere() ? $former->place : null,
            $this->channel->isJoinedByALink() ? $former->link : null,
        );
    }
}
