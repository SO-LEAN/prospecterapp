<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Recipient\ListCampaignRecipients;

/**
 * Lists a campaign's recipients, and what the member may do with them. The query writes nothing.
 */
final readonly class ListCampaignRecipients
{
    public function __construct(
        public string $memberId,
        public string $campaignId,
    ) {
    }
}
