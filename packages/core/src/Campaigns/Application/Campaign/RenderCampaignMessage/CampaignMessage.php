<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Campaign\RenderCampaignMessage;

/** A campaign's message rendered for one contact, and an e-mail's subject. */
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
