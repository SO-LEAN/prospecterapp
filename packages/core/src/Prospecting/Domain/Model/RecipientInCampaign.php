<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\CampaignNotRunning;
use Prospecter\Core\Prospecting\Domain\Exception\RecipientAlreadyProcessed;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownRecipient;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact as a campaign in Campaigns keeps them, when a member logs an exchange for them in it (ADR 0145). It holds
 * the campaign's channel, whether the campaign is running now, whether the contact is one of its recipients, and
 * whether they still need handling.
 */
final readonly class RecipientInCampaign
{
    public function __construct(
        public CampaignId $campaign,
        public PersonId $contact,
        public Channel $channel,
        public bool $running,
        public bool $recipient,
        public bool $pending,
    ) {
    }

    /**
     * What the exchange logged for them in the campaign tells: it is outbound, through the campaign's channel.
     */
    #[\NoDiscard]
    public function exchangeFacts(\DateTimeImmutable $occurredAt, ?string $text): ExchangeFacts
    {
        return new ExchangeFacts($this->channel, Direction::Outbound, $occurredAt, $text);
    }

    /**
     * What tells a sending to them apart in the account. It is the same when an AI logs the sending and when it asks
     * how the sending was logged (ADR 0166, 0195).
     */
    #[\NoDiscard]
    public function fingerprintOfSending(\DateTimeImmutable $occurredAt, ?string $text, MessageReference $reference): ExchangeFingerprint
    {
        return $reference->fingerprintOf($this->exchangeFacts($occurredAt, $text), $this->contact);
    }

    /**
     * @throws CampaignNotRunning
     * @throws UnknownRecipient
     * @throws RecipientAlreadyProcessed
     */
    public function ensureToHandle(): void
    {
        match (true) {
            !$this->running => throw CampaignNotRunning::toRun(),
            !$this->recipient => throw UnknownRecipient::identifiedBy($this->contact),
            !$this->pending => throw RecipientAlreadyProcessed::once(),
            default => null,
        };
    }
}
