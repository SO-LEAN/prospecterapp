<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Policy;

use Prospecter\Core\Campaigns\Domain\Model\CampaignIntent;
use Prospecter\Core\Campaigns\Domain\Model\CampaignStatus;

/**
 * What a campaign offers in its state (ADR 0144): a draft is revised, discarded and frozen; frozen, its recipients
 * change by hand, its name and its message are still revised, it is still discarded, and it is started while it keeps
 * someone to contact; started, it is led and stopped; stopped or ended, it offers nothing.
 */
final class CampaignPossibilities
{
    /**
     * @return list<CampaignIntent>
     */
    #[\NoDiscard]
    public static function given(CampaignStatus $status, bool $hasRecipients): array
    {
        return match ($status) {
            CampaignStatus::Draft => [CampaignIntent::ReviseCampaign, CampaignIntent::DiscardCampaign, CampaignIntent::FreezeCampaign],
            CampaignStatus::Frozen => [CampaignIntent::ReviseCampaign, CampaignIntent::DiscardCampaign, CampaignIntent::AddRecipients, CampaignIntent::RemoveRecipient, ...($hasRecipients ? [CampaignIntent::StartCampaign] : [])],
            CampaignStatus::Started => [CampaignIntent::RunCampaign, CampaignIntent::StopCampaign],
            CampaignStatus::Stopped, CampaignStatus::Ended => [],
        };
    }
}
