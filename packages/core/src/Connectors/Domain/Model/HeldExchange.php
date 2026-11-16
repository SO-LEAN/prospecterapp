<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * An exchange as Connectors knows it from Prospecting, read as it is now (ADR 0127): who holds it, where it stands,
 * when, how and where it is held, with whom, whether a video call without a link asks its calendar for one (ADR
 * 0137). Noted once it happened, it has no channel to plan.
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
        public ?PersonId $contact,
        private OrganizationId $organization,
        private string $organizationName,
        private bool $linkRequested = false,
    ) {
    }

    /**
     * A planned exchange, a call, a meeting or a video call, is in the calendar of its holder.
     */
    public function belongsInTheCalendar(): bool
    {
        return ExchangeStatus::Planned === $this->status && null !== $this->channel;
    }

    /**
     * Planned and still to come at the moment: its state is pending until a push reaches the calendar (ADR 0133).
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
     * A cancelled exchange leaves the calendar; one done or missed stays there as it was.
     */
    public function isCalledOff(): bool
    {
        return ExchangeStatus::Cancelled === $this->status;
    }

    /**
     * Done or missed, its event stays; its contact forgotten, it no longer names them.
     */
    public function staysInTheCalendar(): bool
    {
        return in_array($this->status, [ExchangeStatus::Done, ExchangeStatus::Missed], true) && null !== $this->channel;
    }

    /**
     * Its event, its contact named as its holder reads them, reminding them as they chose; a contact nobody names any
     * more, forgotten, leaves it, the event leading to the organization.
     */
    public function asCalendarEvent(?string $contactName, ?int $reminderMinutesBefore): CalendarEvent
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
        );
    }

    private function asksForALink(): bool
    {
        return $this->linkRequested && Channel::VideoCall === $this->channel && null === $this->venue;
    }
}
