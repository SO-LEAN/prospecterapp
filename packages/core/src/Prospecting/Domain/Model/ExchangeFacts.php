<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\ChannelNotMissed;
use Prospecter\Core\Prospecting\Domain\Exception\ChannelNotPlanned;
use Prospecter\Core\Prospecting\Domain\Exception\LinkOnlyForAVideoCall;
use Prospecter\Core\Prospecting\Domain\Exception\PlaceOnlyForAMeeting;

/**
 * What happened between the prospector and the contact in the real world, or what they agreed upon: the moment,
 * and how long it lasts, of a planned exchange; where a meeting is held, written freely, and the link a video
 * call is joined by, or asked of the calendar of its holder while it has none (ADR 0137); the subject of an email,
 * if any, is in the text.
 */
final readonly class ExchangeFacts
{
    public ?string $text;
    public ?string $place;
    public bool $linkRequested;

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
        bool $linkRequested = false,
    ) {
        $this->text = self::orNone($text);
        $this->place = self::orNone($place);
        self::ensureTheVenueFits($channel, $this->place, $link, $linkRequested);
        $this->linkRequested = $linkRequested && null === $link;
    }

    /**
     * Only a call, a meeting or a video call may be missed.
     *
     * @throws ChannelNotMissed
     */
    public function ensureMissable(ExchangeStatus $outcome): void
    {
        if (ExchangeStatus::Missed === $outcome && !$this->channel->mayBePlanned()) {
            throw ChannelNotMissed::because('Only a call, a meeting or a video call may be missed', 'missed', context: ['channel' => $this->channel->value]);
        }
    }

    private static function orNone(?string $written): ?string
    {
        return null === $written || '' === trim($written) ? null : $written;
    }

    /**
     * @throws PlaceOnlyForAMeeting|LinkOnlyForAVideoCall
     */
    private static function ensureTheVenueFits(Channel $channel, ?string $place, ?VideoCallLink $link, bool $linkRequested): void
    {
        if (null !== $place && !$channel->isHeldSomewhere()) {
            throw PlaceOnlyForAMeeting::because('Only a meeting has a place', 'place', context: ['channel' => $channel->value]);
        }

        if ((null !== $link || $linkRequested) && !$channel->isJoinedByALink()) {
            throw LinkOnlyForAVideoCall::because('Only a video call has a link', 'link', context: ['channel' => $channel->value]);
        }
    }

    /**
     * Agreed upon with the contact, at the prospector's initiative; the text is the agenda. withVenue() gives the place
     * or the link, askingALink() the request of a link.
     *
     * @throws ChannelNotPlanned
     */
    public static function planned(Channel $channel, \DateTimeImmutable $at, Duration $duration, ?string $agenda): self
    {
        if (!$channel->mayBePlanned()) {
            throw ChannelNotPlanned::because('Only a call, a meeting or a video call may be planned', 'channel', context: ['channel' => $channel->value]);
        }

        return new self($channel, Direction::Outbound, $at, $agenda, $duration);
    }

    /**
     * A video call asking its link of the calendar of its holder, while it has none (ADR 0137).
     *
     * @throws LinkOnlyForAVideoCall
     */
    public function askingALink(): self
    {
        return new self($this->channel, $this->direction, $this->occurredAt, $this->text, $this->duration, $this->place, $this->link, linkRequested: true);
    }

    public function withText(?string $text): self
    {
        return new self($this->channel, $this->direction, $this->occurredAt, $text, $this->duration, $this->place, $this->link, $this->linkRequested);
    }

    /**
     * As it took place, at the moment it did, with what was said, where it was held.
     */
    public function happened(\DateTimeImmutable $at, ?string $report): self
    {
        return new self($this->channel, $this->direction, $at, $report, $this->duration, $this->place, $this->link, $this->linkRequested);
    }

    public function movedTo(\DateTimeImmutable $at, ?Duration $duration): self
    {
        return new self($this->channel, $this->direction, $at, $this->text, $duration ?? $this->duration, $this->place, $this->link, $this->linkRequested);
    }

    /**
     * A link given ends the request of one.
     *
     * @throws PlaceOnlyForAMeeting|LinkOnlyForAVideoCall
     */
    public function withVenue(?string $place, ?VideoCallLink $link): self
    {
        return new self($this->channel, $this->direction, $this->occurredAt, $this->text, $this->duration, $place, $link, $this->linkRequested);
    }

    /**
     * Corrected as a whole, the facts keep where the exchange was held, or its link, or the request of one, while
     * their channel still has one.
     */
    public function keepingTheVenueOf(self $former): self
    {
        $joinedByALink = $this->channel->isJoinedByALink();

        return new self(
            $this->channel,
            $this->direction,
            $this->occurredAt,
            $this->text,
            $this->duration,
            $this->channel->isHeldSomewhere() ? $former->place : null,
            $joinedByALink ? $former->link : null,
            $joinedByALink && $former->linkRequested,
        );
    }
}
