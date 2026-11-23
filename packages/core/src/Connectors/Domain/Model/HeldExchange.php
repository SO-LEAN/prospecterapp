<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * An exchange as Connectors knows it from Prospecting, read as it currently is (ADR 0127). It holds who holds it and
 * its status. It holds when, how and where it takes place, and with whom. It says whether a video call without a link
 * asks its calendar for one (ADR 0137). An exchange logged after it happened has no channel to plan.
 */
final readonly class HeldExchange
{
    public function __construct(
        public ExchangeId $id,
        public AccountId $accountId,
        public MemberId $holder,
        private ExchangeStatus $status,
        private ?Channel $channel,
        public \DateTimeImmutable $startsAt,
        private ?int $durationInMinutes,
        private ?string $venue,
        public ?ContactId $contact,
        private ?OrganizationId $organization,
        private ?string $organizationName,
        private bool $linkRequested = false,
        private bool $bookedByLink = false,
    ) {
    }

    /**
     * A planned call, meeting or video call belongs in its holder's calendar.
     */
    public function belongsInTheCalendar(): bool
    {
        return ExchangeStatus::Planned === $this->status && null !== $this->channel;
    }

    /**
     * The exchange is planned and still to come at that moment. Its push state is pending until a push reaches the
     * calendar (ADR 0133).
     */
    public function awaitsItsPushAt(\DateTimeImmutable $at): bool
    {
        return $this->belongsInTheCalendar() && $this->startsAt >= $at;
    }

    public function isHeldBy(MemberId $member): bool
    {
        return $this->holder->equals($member);
    }

    /**
     * A cancelled exchange leaves the calendar. A done or missed exchange stays there as it was.
     */
    public function isCalledOff(): bool
    {
        return ExchangeStatus::Cancelled === $this->status;
    }

    /**
     * A done or missed exchange keeps its event. Once its contact is forgotten, the event no longer names them.
     */
    public function staysInTheCalendar(): bool
    {
        return in_array($this->status, [ExchangeStatus::Done, ExchangeStatus::Missed], true) && null !== $this->channel;
    }

    /**
     * The exchange is a call: the holder calls its contact.
     */
    public function isACall(): bool
    {
        return Channel::Call === $this->channel;
    }

    /**
     * The contact booked the exchange through its holder's public link. So its event invites that contact.
     */
    public function invitesItsContact(): bool
    {
        return $this->bookedByLink && null !== $this->contact;
    }

    /**
     * Builds the exchange's event. The event names the contact as the holder reads them, and reminds the holder as they
     * chose. A forgotten contact has no name any more. Then the event leaves the contact out and leads to the
     * organization.
     */
    public function asCalendarEvent(?string $contactName, ?int $reminderMinutesBefore, ?Guest $guest = null): CalendarEvent
    {
        return new CalendarEvent(
            $this->id,
            $this->channel ?? throw new \LogicException(sprintf('The exchange %s is not planned', $this->id)),
            $this->startsAt,
            $this->startsAt->modify(sprintf('+%d minutes', $this->durationInMinutes ?? 0)),
            $this->venue,
            null === $contactName ? null : $this->contact,
            $contactName,
            $this->organization,
            $this->organizationName,
            $reminderMinutesBefore,
            $this->asksForALink(),
            $guest,
        );
    }

    private function asksForALink(): bool
    {
        return $this->linkRequested && $this->belongsInTheCalendar() && Channel::VideoCall === $this->channel && null === $this->venue;
    }
}
