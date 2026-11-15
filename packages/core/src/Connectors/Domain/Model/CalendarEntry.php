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
 * contact once forgotten.
 */
final class CalendarEntry implements ReleasesEvents
{
    use RecordsEvents;

    private function __construct(
        private readonly ExchangeId $exchange,
        private readonly AccountId $accountId,
        private readonly MemberId $member,
        private readonly Provider $provider,
        private readonly ?PersonId $contact,
    ) {
    }

    public static function of(HeldExchange $exchange, Provider $provider): self
    {
        return new self($exchange->id, $exchange->accountId, $exchange->holder, $provider, $exchange->contact);
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
