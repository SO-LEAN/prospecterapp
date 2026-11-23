<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact that a frozen campaign keeps to contact. The campaign keeps them by id only, because their name belongs to
 * PersonalData. A processed recipient keeps the id of the Prospecting exchange logged for them (ADR 0152), and whether
 * the call went through. A dismissed recipient keeps the reason, when one is given. A processed recipient also keeps
 * when they were processed, for the campaign's daily cap.
 *
 * PersonalData may erase the contact from a started campaign. The recipient is then kept without their name (ADR 0145).
 * What the team did with them stays: processed, missed or dismissed. A recipient still to handle no longer is. Neither
 * the exchange nor the reason stays, because both may say something about the person.
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
        public ?\DateTimeImmutable $processedAt = null,
    ) {
    }

    public static function pending(PersonId $contact): self
    {
        return new self($contact, RecipientStatus::Pending);
    }

    #[\NoDiscard]
    public function processed(string $exchangeId, RecipientOutcome $outcome, \DateTimeImmutable $at): self
    {
        return new self($this->contact, RecipientStatus::Processed, RecipientOutcome::Missed === $outcome, exchangeId: $exchangeId, processedAt: $at);
    }

    #[\NoDiscard]
    public function dismissed(?string $reason): self
    {
        return new self($this->contact, RecipientStatus::Dismissed, reason: $reason);
    }

    /** Once the contact is erased, what the team did with them stays. */
    #[\NoDiscard]
    public function erased(): self
    {
        return new self($this->contact, $this->isPending() ? RecipientStatus::Erased : $this->status, $this->missed, erased: true, processedAt: $this->processedAt);
    }

    /**
     * The exchange went with its organization, and no member erased it. What the team did stays, but the recipient no
     * longer links to the exchange (ADR 0155).
     */
    #[\NoDiscard]
    public function withoutItsExchange(): self
    {
        return new self($this->contact, $this->status, $this->missed, $this->reason, erased: $this->erased, processedAt: $this->processedAt);
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
     * Whether this exchange processed the recipient and they still link to it. A recipient erased since links to no
     * exchange.
     */
    #[\NoDiscard]
    public function isProcessedBy(string $exchangeId): bool
    {
        return $exchangeId === $this->exchangeId;
    }
}
