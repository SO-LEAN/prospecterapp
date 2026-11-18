<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact a frozen campaign keeps to be contacted, by its identity only: its name is PersonalData's. Processed, by
 * the identity of the exchange of Prospecting noted for them (ADR 0152), whether the call did not go through;
 * dismissed, why, when said.
 */
final readonly class Recipient
{
    public function __construct(
        public PersonId $contact,
        public RecipientStatus $status,
        public bool $missed = false,
        public ?string $reason = null,
        public ?string $exchangeId = null,
    ) {
    }

    public static function pending(PersonId $contact): self
    {
        return new self($contact, RecipientStatus::Pending);
    }

    #[\NoDiscard]
    public function processed(string $exchangeId, bool $missed): self
    {
        return new self($this->contact, RecipientStatus::Processed, $missed, exchangeId: $exchangeId);
    }

    #[\NoDiscard]
    public function dismissed(?string $reason): self
    {
        return new self($this->contact, RecipientStatus::Dismissed, reason: $reason);
    }

    #[\NoDiscard]
    public function erased(): self
    {
        return new self($this->contact, RecipientStatus::Erased);
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
     * Processed by this exchange, and still: a recipient erased since is no longer.
     */
    #[\NoDiscard]
    public function isProcessedBy(string $exchangeId): bool
    {
        return RecipientStatus::Processed === $this->status && $exchangeId === $this->exchangeId;
    }
}
