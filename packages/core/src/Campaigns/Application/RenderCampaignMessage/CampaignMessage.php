<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\RenderCampaignMessage;

/**
 * The message of a campaign rendered for one contact, and the subject of an e-mail.
 */
final readonly class CampaignMessage
{
    public function __construct(
        public string $campaignId,
        public string $contactId,
        public ?string $subject,
        public string $text,
    ) {
    }
}
