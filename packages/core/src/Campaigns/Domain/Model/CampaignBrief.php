<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * What a campaign is about, as a member writes it when they prepare or revise it. It holds the campaign's name, whom it
 * targets and the message it sends them.
 */
final readonly class CampaignBrief
{
    public function __construct(
        public CampaignName $name,
        public Targeting $targeting,
        public MessageTemplate $message,
    ) {
    }
}
