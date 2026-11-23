<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Event\ContactLeftUnattached;
use Prospecter\Core\Prospecting\Domain\Event\ContactToAttachAttached;
use Prospecter\Core\Prospecting\Domain\Event\ContactToAttachHandedOver;
use Prospecter\Core\Prospecting\Domain\Event\ContactToAttachMet;
use Prospecter\Core\Prospecting\Domain\Event\ContactToAttachMetAgain;
use Prospecter\Core\Prospecting\Domain\Rule\UnattachedErasure;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: a contact of the account that no organization names yet. The person took a meeting with a member
 * before anyone knew whom they work for (ADR 0146). The member who meets them holds them, with their exchanges and
 * to-dos, until someone attaches them to an organization. A contact who is never attached is erased from the start of
 * the 30th day after their last meeting. That day is read on the clock the meeting was given in.
 */
final class ContactToAttach implements ReleasesEvents
{
    use RecordsEvents;

    private \DateTimeImmutable $modifiedAt;

    private function __construct(
        private readonly ContactId $id,
        private readonly AccountId $accountId,
        private MemberId $holder,
        private \DateTimeImmutable $lastMeetingAt,
        private readonly \DateTimeZone $clock,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->modifiedAt = $createdAt;
    }

    /**
     * The meeting's clock is kept, because the erasure's day is read on it.
     */
    public static function metBy(Prospector $holder, ContactId $contact, \DateTimeImmutable $meeting, \DateTimeImmutable $at): self
    {
        $toAttach = new self($contact, $holder->accountId, $holder->id, $meeting, $meeting->getTimezone(), $at);
        $toAttach->recordThat(new ContactToAttachMet($holder->accountId, $holder->id, $contact, $at));

        return $toAttach;
    }

    public function id(): ContactId
    {
        return $this->id;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function isHeldBy(MemberId $member): bool
    {
        return $this->holder->equals($member);
    }

    public function isLeftUnattachedAt(\DateTimeImmutable $at): bool
    {
        return $at >= UnattachedErasure::from($this->lastMeetingAt, $this->clock);
    }

    /**
     * A meeting after the last one pushes the erasure back. An earlier one changes nothing.
     */
    public function meetAgainAt(\DateTimeImmutable $meeting, \DateTimeImmutable $at): void
    {
        if ($meeting <= $this->lastMeetingAt) {
            return;
        }

        $this->lastMeetingAt = $meeting;
        $this->modifiedAt = $at;
        $this->recordThat(new ContactToAttachMetAgain($this->accountId, $this->holder, $this->id, $at));
    }

    /**
     * The contact booked through another member's public link. From then on, that member meets and holds them. What
     * the former holder logged with them stays the former holder's (ADR 0164).
     */
    public function handOverTo(Prospector $holder, \DateTimeImmutable $meeting, \DateTimeImmutable $at): void
    {
        $this->holder = $holder->id;
        $this->lastMeetingAt = max($this->lastMeetingAt, $meeting);
        $this->modifiedAt = $at;
        $this->recordThat(new ContactToAttachHandedOver($this->accountId, $holder->id, $this->id, $at));
    }

    public function attachTo(OrganizationId $organization, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->modifiedAt = $at;
        $this->recordThat(new ContactToAttachAttached($this->accountId, $by, $this->id, $organization, $at));
    }

    /**
     * Its holder, the member who collected the contact, erases them because nobody attached them in time.
     *
     * @throws NotPossibleInItsState
     */
    public function eraseLeftUnattached(\DateTimeImmutable $at): void
    {
        if (!$this->isLeftUnattachedAt($at)) {
            throw NotPossibleInItsState::because('A contact to attach is erased from the 30th day after their last meeting');
        }

        $this->modifiedAt = $at;
        $this->recordThat(new ContactLeftUnattached($this->accountId, $this->holder, $this->id, $at));
    }
}
