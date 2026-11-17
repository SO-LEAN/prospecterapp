<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Policy;

use Prospecter\Core\Campaigns\Domain\Model\CampaignIntent;
use Prospecter\Core\Campaigns\Domain\Model\CampaignStatus;

/**
 * What a campaign offers in its state (ADR 0144): a draft is revised, discarded and frozen; frozen, its recipients
 * change by hand, its name and its message are still revised, and it is still discarded.
 */
final class CampaignPossibilities
{
    /**
     * @return list<CampaignIntent>
     */
    #[\NoDiscard]
    public static function given(CampaignStatus $status): array
    {
        return match ($status) {
            CampaignStatus::Draft => [CampaignIntent::ReviseCampaign, CampaignIntent::DiscardCampaign, CampaignIntent::FreezeCampaign],
            CampaignStatus::Frozen => [CampaignIntent::ReviseCampaign, CampaignIntent::DiscardCampaign, CampaignIntent::AddRecipients, CampaignIntent::RemoveRecipient],
        };
    }
}
