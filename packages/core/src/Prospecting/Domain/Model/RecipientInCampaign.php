<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\CampaignNotRunning;
use Prospecter\Core\Prospecting\Domain\Exception\RecipientAlreadyProcessed;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownRecipient;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact as a campaign of Campaigns keeps them, when an exchange is noted for them in it (ADR 0145): the channel
 * of the campaign, whether it is led now, whether the contact is one of its recipients, and still to handle.
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
