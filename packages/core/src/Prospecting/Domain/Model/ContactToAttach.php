<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Event\ContactLeftUnattached;
use Prospecter\Core\Prospecting\Domain\Event\ContactToAttachAttached;
use Prospecter\Core\Prospecting\Domain\Event\ContactToAttachMet;
use Prospecter\Core\Prospecting\Domain\Event\ContactToAttachMetAgain;
use Prospecter\Core\Prospecting\Domain\Policy\UnattachedErasure;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: a contact of the account no organization names yet, a person who took a meeting with a member
 * before anyone knew for whom they work (ADR 0146). The member who meets them holds them, and their exchanges and
 * to-dos, until they attach them to an organization; never attached, they are erased from the start of the 30th
 * day after their last meeting, read on the clock that meeting was given in.
 */
final class ContactToAttach implements ReleasesEvents
{
    use RecordsEvents;

    private \DateTimeImmutable $modifiedAt;

    private function __construct(
        private readonly PersonId $id,
        private readonly AccountId $accountId,
        private readonly MemberId $holder,
        private \DateTimeImmutable $lastMeetingAt,
        private readonly \DateTimeZone $clock,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->modifiedAt = $createdAt;
    }

    /**
     * The clock of the meeting is kept: the day of the erasure is read on it.
     */
    public static function metBy(Prospector $holder, PersonId $contact, \DateTimeImmutable $meeting, \DateTimeImmutable $at): self
    {
        $toAttach = new self($contact, $holder->accountId, $holder->id, $meeting, $meeting->getTimezone(), $at);
        $toAttach->recordThat(new ContactToAttachMet($holder->accountId, $holder->id, $contact, $at));

        return $toAttach;
    }

    public function id(): PersonId
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
     * A meeting after the last one pushes the erasure back; an earlier one changes nothing.
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

    public function attachTo(OrganizationId $organization, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->modifiedAt = $at;
        $this->recordThat(new ContactToAttachAttached($this->accountId, $by, $this->id, $organization, $at));
    }

    /**
     * By its holder, the member who collected them: nobody attached them in time.
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
