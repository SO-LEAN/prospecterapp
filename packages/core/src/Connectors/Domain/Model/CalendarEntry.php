<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Event\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\Event\ReleasesEvents;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Aggregate root: an exchange that Connectors pushed into its holder's calendar at a provider (ADR 0127). Connectors
 * keeps it to withdraw the event once Prospecting erased the exchange and no longer knows whose it was. It also keeps
 * it to put the event again without the contact's name once the contact is forgotten. The entry holds the push state,
 * the moment it took that state, and why the push failed (ADR 0133).
 */
final class CalendarEntry implements ReleasesEvents
{
    use RecordsEvents;

    private SyncState $state = SyncState::Pending;
    private ?string $reason = null;
    private bool $invitesAGuest = false;

    private function __construct(
        private readonly ExchangeId $exchange,
        private readonly AccountId $accountId,
        private readonly MemberId $member,
        private readonly Provider $provider,
        private readonly ?ContactId $contact,
        private \DateTimeImmutable $startsAt,
        private \DateTimeImmutable $since,
    ) {
    }

    public static function of(HeldExchange $exchange, Provider $provider, \DateTimeImmutable $at): self
    {
        return new self($exchange->id, $exchange->accountId, $exchange->holder, $provider, $exchange->contact, $exchange->startsAt, $at);
    }

    /**
     * The push reached the calendar with the exchange as it was then.
     */
    public function pushed(HeldExchange $exchange, \DateTimeImmutable $at): void
    {
        $this->startsAt = $exchange->startsAt;
        $this->invitesAGuest = $exchange->invitesItsContact();
        $this->becomes(SyncState::Synced, null, $at);
    }

    /**
     * The provider refused the push for good. The member decides whether to push the exchange again.
     */
    public function refused(string $reason, \DateTimeImmutable $at): void
    {
        $this->becomes(SyncState::Failed, $reason, $at);
    }

    /**
     * A push reached nothing because the connection is to reconnect. The calendar may miss what changed.
     */
    public function awaits(\DateTimeImmutable $at): void
    {
        $this->becomes(SyncState::Pending, null, $at);
    }

    private function becomes(SyncState $state, ?string $reason, \DateTimeImmutable $at): void
    {
        $this->state = $state;
        $this->reason = $reason;
        $this->since = $at;
    }

    /**
     * A push tells the guest about the exchange when its event is new to them or has moved. It does not tell them
     * about a link, a reminder or a name put again.
     */
    public function tellsItsGuestOfAPushOf(HeldExchange $exchange): bool
    {
        return !$this->invitesAGuest || $this->startsAt != $exchange->startsAt;
    }

    /**
     * The event invites a guest and is still to come. When it is withdrawn, the provider tells the guest that it is
     * cancelled.
     */
    public function invitesAGuestStillToComeAt(\DateTimeImmutable $at): bool
    {
        return $this->invitesAGuest && $this->startsAt >= $at;
    }

    public function hasFailed(): bool
    {
        return SyncState::Failed === $this->state;
    }

    public function exchange(): ExchangeId
    {
        return $this->exchange;
    }

    public function member(): MemberId
    {
        return $this->member;
    }

    public function contact(): ?ContactId
    {
        return $this->contact;
    }

    public function provider(): Provider
    {
        return $this->provider;
    }
}
