<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Exception\CampaignWithoutRecipient;
use Prospecter\Core\Campaigns\Domain\Exception\RecipientAlreadyProcessed;
use Prospecter\Core\Campaigns\Domain\Exception\TooManyRecipients;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownRecipient;

/**
 * The contacts that a frozen campaign keeps, each once, in the order added. The members keep 1 to 500 of them. A draft
 * keeps none.
 */
final readonly class Recipients
{
    public const int MAX = 500;

    /**
     * @param list<Recipient> $recipients
     */
    private function __construct(public array $recipients)
    {
    }

    public static function none(): self
    {
        return new self([]);
    }

    /**
     * @param list<ContactId> $contacts
     *
     * @throws CampaignWithoutRecipient
     * @throws TooManyRecipients
     */
    public static function frozen(array $contacts): self
    {
        return [] === $contacts ? throw CampaignWithoutRecipient::left() : self::none()->with($contacts);
    }

    /**
     * A contact already kept is kept once.
     *
     * @param list<ContactId> $contacts
     *
     * @throws TooManyRecipients
     */
    #[\NoDiscard]
    public function with(array $contacts): self
    {
        $recipients = $this->recipients;

        foreach ($contacts as $contact) {
            if (!array_any($recipients, static fn (Recipient $recipient): bool => $recipient->is($contact))) {
                $recipients[] = Recipient::pending($contact);
            }
        }

        return count($recipients) > self::MAX ? throw TooManyRecipients::beyond(self::MAX) : new self($recipients);
    }

    /**
     * @throws UnknownRecipient
     * @throws CampaignWithoutRecipient
     */
    #[\NoDiscard]
    public function without(ContactId $contact): self
    {
        if (!$this->includes($contact)) {
            throw UnknownRecipient::identifiedBy($contact);
        }

        $left = $this->forgetting($contact);

        return [] === $left->recipients ? throw CampaignWithoutRecipient::left() : $left;
    }

    /**
     * A contact that PersonalData forgot leaves, even when it is the last one. A person's erasure wins over the team's
     * list.
     */
    #[\NoDiscard]
    public function forgetting(ContactId $contact): self
    {
        return new self(array_values(array_filter($this->recipients, static fn (Recipient $recipient): bool => !$recipient->is($contact))));
    }

    /**
     * A contact that PersonalData erased stays among a started campaign's recipients, without their name and no longer
     * to handle. What the team did stays recorded.
     */
    #[\NoDiscard]
    public function erasing(ContactId $contact): self
    {
        return $this->handling($contact, static fn (Recipient $recipient): Recipient => $recipient->erased());
    }

    /**
     * The exchange logged for a recipient still to handle processes them. A recipient already handled stays as it is:
     * the first exchange wins.
     */
    #[\NoDiscard]
    public function processing(ContactId $contact, string $exchangeId, RecipientOutcome $outcome, \DateTimeImmutable $at): self
    {
        return $this->handling($contact, static fn (Recipient $recipient): Recipient => $recipient->isPending() ? $recipient->processed($exchangeId, $outcome, $at) : $recipient);
    }

    /**
     * @return list<?\DateTimeImmutable>
     */
    #[\NoDiscard]
    public function processedAt(): array
    {
        return array_map(static fn (Recipient $recipient): ?\DateTimeImmutable => $recipient->processedAt, $this->recipients);
    }

    /**
     * The recipient that the erased exchange processed is to handle again. Another of their exchanges changes nothing
     * (ADR 0152).
     */
    #[\NoDiscard]
    public function reopening(string $exchangeId): self
    {
        return new self(array_map(static fn (Recipient $recipient): Recipient => $recipient->isProcessedBy($exchangeId) ? Recipient::pending($recipient->contact) : $recipient, $this->recipients));
    }

    /**
     * The exchange went with its organization. The recipient it processed stays processed, without the exchange (ADR
     * 0155).
     */
    #[\NoDiscard]
    public function forgettingExchange(string $exchangeId): self
    {
        return new self(array_map(static fn (Recipient $recipient): Recipient => $recipient->isProcessedBy($exchangeId) ? $recipient->withoutItsExchange() : $recipient, $this->recipients));
    }

    #[\NoDiscard]
    public function processedBy(string $exchangeId): bool
    {
        return array_any($this->recipients, static fn (Recipient $recipient): bool => $recipient->isProcessedBy($exchangeId));
    }

    /**
     * @throws UnknownRecipient
     * @throws RecipientAlreadyProcessed when the recipient is no longer to handle
     */
    #[\NoDiscard]
    public function dismissing(ContactId $contact, ?string $reason): self
    {
        $recipient = array_find($this->recipients, static fn (Recipient $recipient): bool => $recipient->is($contact)) ?? throw UnknownRecipient::identifiedBy($contact);

        return $recipient->isPending() ? $this->handling($contact, static fn (Recipient $pending): Recipient => $pending->dismissed($reason)) : throw RecipientAlreadyProcessed::once();
    }

    #[\NoDiscard]
    public function nonePending(): bool
    {
        return !array_any($this->recipients, static fn (Recipient $recipient): bool => $recipient->isPending());
    }

    /**
     * The recipients to handle in the order added. It skips those that an exchange was logged with meanwhile. It is
     * empty once nobody else is left.
     *
     * @param list<ContactId> $exchangedWith
     */
    #[\NoDiscard]
    public function firstPending(array $exchangedWith): ?ContactId
    {
        return ($this->pendingBut($exchangedWith)[0] ?? null)?->contact;
    }

    /**
     * @param list<ContactId> $exchangedWith
     */
    #[\NoDiscard]
    public function pendingCount(array $exchangedWith): int
    {
        return count($this->pendingBut($exchangedWith));
    }

    /**
     * @return list<ContactId>
     */
    #[\NoDiscard]
    public function pending(): array
    {
        return array_map(static fn (Recipient $recipient): ContactId => $recipient->contact, $this->pendingBut([]));
    }

    /**
     * @param list<ContactId> $exchangedWith
     *
     * @return list<Recipient>
     */
    private function pendingBut(array $exchangedWith): array
    {
        return array_values(array_filter($this->recipients, static fn (Recipient $recipient): bool => $recipient->isPending() && !array_any($exchangedWith, $recipient->is(...))));
    }

    #[\NoDiscard]
    public function keepsSomeone(): bool
    {
        return [] !== $this->recipients;
    }

    #[\NoDiscard]
    public function includes(ContactId $contact): bool
    {
        return array_any($this->recipients, static fn (Recipient $recipient): bool => $recipient->is($contact));
    }

    /**
     * @param \Closure(Recipient): Recipient $change
     */
    private function handling(ContactId $contact, \Closure $change): self
    {
        return new self(array_map(static fn (Recipient $recipient): Recipient => $recipient->is($contact) ? $change($recipient) : $recipient, $this->recipients));
    }
}
