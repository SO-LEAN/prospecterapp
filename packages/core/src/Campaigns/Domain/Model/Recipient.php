<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact a frozen campaign keeps to be contacted, by its identity only: its name is PersonalData's. Processed, by
 * the identity of the exchange of Prospecting noted for them (ADR 0152), whether the call did not go through;
 * dismissed, why, when said. Erased by PersonalData from a started campaign, kept without its name (ADR 0145): what
 * the team did with them stays, processed, missed or dismissed, and one still to handle is no longer; neither the
 * exchange nor the reason, which may tell of the person, are kept.
 */
final readonly class Recipient
{
    public function __construct(
        public PersonId $contact,
        public RecipientStatus $status,
        public bool $missed = false,
        public ?string $reason = null,
        public ?string $exchangeId = null,
        public bool $erased = false,
    ) {
    }

    public static function pending(PersonId $contact): self
    {
        return new self($contact, RecipientStatus::Pending);
    }

    #[\NoDiscard]
    public function processed(string $exchangeId, RecipientOutcome $outcome): self
    {
        return new self($this->contact, RecipientStatus::Processed, RecipientOutcome::Missed === $outcome, exchangeId: $exchangeId);
    }

    #[\NoDiscard]
    public function dismissed(?string $reason): self
    {
        return new self($this->contact, RecipientStatus::Dismissed, reason: $reason);
    }

    /**
     * Its contact erased, what the team did with them stays.
     */
    #[\NoDiscard]
    public function erased(): self
    {
        return new self($this->contact, $this->isPending() ? RecipientStatus::Erased : $this->status, $this->missed, erased: true);
    }

    /**
     * Its exchange gone with its organization, no member erasing it: what the team did stays, without the exchange to
     * lead to (ADR 0155).
     */
    #[\NoDiscard]
    public function withoutItsExchange(): self
    {
        return new self($this->contact, $this->status, $this->missed, $this->reason, erased: $this->erased);
    }

    #[\NoDiscard]
    public function is(PersonId $contact): bool
    {
        return $this->contact->equals($contact);
    }

    #[\NoDiscard]
    public function isPending(): bool
    {
        return RecipientStatus::Pending === $this->status;
    }

    /**
     * Processed by this exchange, and still: a recipient erased since keeps none.
     */
    #[\NoDiscard]
    public function isProcessedBy(string $exchangeId): bool
    {
        return $exchangeId === $this->exchangeId;
    }
}
