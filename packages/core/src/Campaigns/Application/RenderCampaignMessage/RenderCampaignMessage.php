<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\RenderCampaignMessage;

/**
 * The message of a campaign as the member sends or says it to one contact of their account (ADR 0144).
 */
final readonly class RenderCampaignMessage
{
    public function __construct(
        public string $memberId,
        public string $campaignId,
        public string $contactId,
    ) {
    }
}
