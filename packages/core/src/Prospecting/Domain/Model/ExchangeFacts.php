<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\ChannelNotMissed;
use Prospecter\Core\Prospecting\Domain\Exception\ChannelNotPlanned;
use Prospecter\Core\Prospecting\Domain\Exception\LinkOnlyForAVideoCall;
use Prospecter\Core\Prospecting\Domain\Exception\PlaceOnlyForAMeeting;

/**
 * What happened between the prospector and the contact in the real world, or what they agreed on. A planned
 * exchange has a moment and a duration. A meeting has a place, written freely. A video call has the link that joins
 * it. While it has none, it may request one from its holder's calendar (ADR 0137). An email's subject, if any, is in
 * the text.
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
     * Only a call, a meeting or a video call can be missed.
     *
     * @throws ChannelNotMissed
     */
    public function ensureMissable(ExchangeStatus $outcome): void
    {
        if (ExchangeStatus::Missed === $outcome && !$this->channel->mayBePlanned()) {
            throw ChannelNotMissed::because('Only a call, a meeting or a video call can be missed', 'missed', context: ['channel' => $this->channel->value]);
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
            throw PlaceOnlyForAMeeting::because('Only a meeting can have a place', 'place', context: ['channel' => $channel->value]);
        }

        if ((null !== $link || $linkRequested) && !$channel->isJoinedByALink()) {
            throw LinkOnlyForAVideoCall::because('Only a video call can have a link', 'link', context: ['channel' => $channel->value]);
        }
    }

    /**
     * The prospector agreed on these facts with the contact, on their own initiative. The text is the agenda.
     * withVenue() gives the place or the link. askingALink() requests a link.
     *
     * @throws ChannelNotPlanned
     */
    public static function planned(Channel $channel, \DateTimeImmutable $at, Duration $duration, ?string $agenda): self
    {
        if (!$channel->mayBePlanned()) {
            throw ChannelNotPlanned::because('Only a call, a meeting or a video call can be planned', 'channel', context: ['channel' => $channel->value]);
        }

        return new self($channel, Direction::Outbound, $at, $agenda, $duration);
    }

    /**
     * A video call without a link requests one from its holder's calendar (ADR 0137).
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
     * The facts as the exchange took place: the moment it did, what was said, and where it was held.
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
     * A link given ends the request for one.
     *
     * @throws PlaceOnlyForAMeeting|LinkOnlyForAVideoCall
     */
    public function withVenue(?string $place, ?VideoCallLink $link): self
    {
        return new self($this->channel, $this->direction, $this->occurredAt, $this->text, $this->duration, $place, $link, $this->linkRequested);
    }

    /**
     * When the facts are corrected as a whole, they keep the exchange's place, its link, or its link request, while
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
