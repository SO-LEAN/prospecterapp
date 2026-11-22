<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\CampaignNotRunning;
use Prospecter\Core\Prospecting\Domain\Exception\RecipientAlreadyProcessed;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownRecipient;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * What a member records about an exchange that took place: its facts and its outcome, done or missed. When known, it
 * also holds where the message was read (ADR 0166), the campaign recipient it was for (ADR 0145), or the imported
 * e-mail it came from (ADR 0154).
 */
final readonly class Noting
{
    private function __construct(
        public ExchangeFacts $facts,
        public ExchangeStatus $outcome,
        private MessageReference $reference,
        private ?RecipientInCampaign $recipient = null,
        public ?ImportedEmail $importedFrom = null,
    ) {
    }

    /**
     * By the hand of the member, or by an AI acting as them.
     */
    public static function of(ExchangeFacts $facts, ExchangeStatus $outcome, ?MessageReference $reference = null): self
    {
        return new self($facts, $outcome, $reference ?? MessageReference::none());
    }

    /**
     * For a recipient of a campaign: outbound, by the channel of the campaign, done, or missed when the call did not
     * go through.
     */
    public static function inACampaign(RecipientInCampaign $recipient, ExchangeFacts $facts, ExchangeStatus $outcome, ?MessageReference $reference = null): self
    {
        return new self($facts, $outcome, $reference ?? MessageReference::none(), $recipient);
    }

    /**
     * By the import of the mailbox of the member, as by them: done.
     */
    public static function imported(ExchangeFacts $facts, ImportedEmail $email): self
    {
        return new self($facts, ExchangeStatus::Done, MessageReference::none(), importedFrom: $email);
    }

    /**
     * Noted in a campaign, while it is led, for one of its recipients still to handle.
     *
     * @throws CampaignNotRunning|UnknownRecipient|RecipientAlreadyProcessed
     */
    public function ensureToHandle(): void
    {
        $this->recipient?->ensureToHandle();
    }

    public function campaign(): ?CampaignId
    {
        return $this->recipient?->campaign;
    }

    public function fingerprintWith(PersonId|OrganizationId $correspondent): ExchangeFingerprint
    {
        if (null !== $this->importedFrom) {
            return ExchangeFingerprint::ofEmail($this->importedFrom->identity, $correspondent);
        }

        return $this->reference->fingerprintOf($this->facts, $correspondent);
    }
}
