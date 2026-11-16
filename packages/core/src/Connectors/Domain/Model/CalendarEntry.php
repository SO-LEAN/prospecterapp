<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: an exchange Connectors pushed into the calendar of its holder at a provider (ADR 0127), kept to
 * withdraw it once Prospecting erased it and no longer knows whose it was, and to put it again without the name of its
 * contact once forgotten; and where its push stands, since when, why it failed (ADR 0133).
 */
final class CalendarEntry implements ReleasesEvents
{
    use RecordsEvents;

    private SyncState $state = SyncState::Pending;
    private ?string $reason = null;

    private function __construct(
        private readonly ExchangeId $exchange,
        private readonly AccountId $accountId,
        private readonly MemberId $member,
        private readonly Provider $provider,
        private readonly ?PersonId $contact,
        private \DateTimeImmutable $startsAt,
        private \DateTimeImmutable $since,
    ) {
    }

    public static function of(HeldExchange $exchange, Provider $provider, \DateTimeImmutable $at): self
    {
        return new self($exchange->id, $exchange->accountId, $exchange->holder, $provider, $exchange->contact, $exchange->startsAt, $at);
    }

    /**
     * The push reached the calendar, the exchange as it was then.
     */
    public function pushed(HeldExchange $exchange, \DateTimeImmutable $at): void
    {
        $this->startsAt = $exchange->startsAt;
        $this->becomes(SyncState::Synced, null, $at);
    }

    /**
     * The provider refused it for good: pushing it again is up to its member.
     */
    public function refused(string $reason, \DateTimeImmutable $at): void
    {
        $this->becomes(SyncState::Failed, $reason, $at);
    }

    /**
     * A push reached nothing, the connection to reconnect: the calendar may lack what changed.
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

    public function contact(): ?PersonId
    {
        return $this->contact;
    }

    public function provider(): Provider
    {
        return $this->provider;
    }
}
