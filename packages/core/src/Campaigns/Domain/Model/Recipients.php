<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Exception\CampaignWithoutRecipient;
use Prospecter\Core\Campaigns\Domain\Exception\RecipientAlreadyProcessed;
use Prospecter\Core\Campaigns\Domain\Exception\TooManyRecipients;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownRecipient;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The contacts a frozen campaign keeps, each once, in the order they were added: from 1 to 500 as the members change
 * them, none in a draft.
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
     * @param list<PersonId> $contacts
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
     * @param list<PersonId> $contacts
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
    public function without(PersonId $contact): self
    {
        if (!$this->includes($contact)) {
            throw UnknownRecipient::identifiedBy($contact);
        }

        $left = $this->forgetting($contact);

        return [] === $left->recipients ? throw CampaignWithoutRecipient::left() : $left;
    }

    /**
     * A contact PersonalData forgot leaves, even the last one: the erasure of a person wins over the list of the team.
     */
    #[\NoDiscard]
    public function forgetting(PersonId $contact): self
    {
        return new self(array_values(array_filter($this->recipients, static fn (Recipient $recipient): bool => !$recipient->is($contact))));
    }

    /**
     * A contact PersonalData erased stays among the recipients of a started campaign, without its name, no longer to
     * handle: what the team did stays told.
     */
    #[\NoDiscard]
    public function erasing(PersonId $contact): self
    {
        return $this->handling($contact, static fn (Recipient $recipient): Recipient => $recipient->erased());
    }

    /**
     * A recipient still to handle is processed by the exchange noted for them; one handled already stays as it is,
     * the first exchange winning.
     */
    #[\NoDiscard]
    public function processing(PersonId $contact, string $exchangeId, bool $missed): self
    {
        return $this->handling($contact, static fn (Recipient $recipient): Recipient => $recipient->isPending() ? $recipient->processed($exchangeId, $missed) : $recipient);
    }

    /**
     * The recipient the erased exchange processed is to handle again; another exchange of theirs changes nothing
     * (ADR 0152).
     */
    #[\NoDiscard]
    public function reopening(string $exchangeId): self
    {
        return new self(array_map(static fn (Recipient $recipient): Recipient => $recipient->isProcessedBy($exchangeId) ? Recipient::pending($recipient->contact) : $recipient, $this->recipients));
    }

    /**
     * The recipient the exchange gone with its organization processed stays processed, without it (ADR 0155).
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
     * @throws RecipientAlreadyProcessed when no longer to handle
     */
    #[\NoDiscard]
    public function dismissing(PersonId $contact, ?string $reason): self
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
     * In the order they were added.
     */
    #[\NoDiscard]
    public function firstPending(): PersonId
    {
        return array_find($this->recipients, static fn (Recipient $recipient): bool => $recipient->isPending())->contact;
    }

    #[\NoDiscard]
    public function pendingCount(): int
    {
        return count(array_filter($this->recipients, static fn (Recipient $recipient): bool => $recipient->isPending()));
    }

    #[\NoDiscard]
    public function keepsSomeone(): bool
    {
        return [] !== $this->recipients;
    }

    #[\NoDiscard]
    public function includes(PersonId $contact): bool
    {
        return array_any($this->recipients, static fn (Recipient $recipient): bool => $recipient->is($contact));
    }

    /**
     * @param \Closure(Recipient): Recipient $change
     */
    private function handling(PersonId $contact, \Closure $change): self
    {
        return new self(array_map(static fn (Recipient $recipient): Recipient => $recipient->is($contact) ? $change($recipient) : $recipient, $this->recipients));
    }
}
