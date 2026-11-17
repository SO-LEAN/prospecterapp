<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Exception\CampaignWithoutRecipient;
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
        return new self(array_map(static fn (Recipient $recipient): Recipient => $recipient->is($contact) ? $recipient->erased() : $recipient, $this->recipients));
    }

    #[\NoDiscard]
    public function includes(PersonId $contact): bool
    {
        return array_any($this->recipients, static fn (Recipient $recipient): bool => $recipient->is($contact));
    }
}
