<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Policy;

use Prospecter\Core\Campaigns\Domain\Model\CampaignIntent;
use Prospecter\Core\Campaigns\Domain\Model\CampaignStatus;

/**
 * What a campaign allows in each state (ADR 0144):
 *
 * - a draft is revised, discarded and frozen;
 * - a frozen campaign's recipients change by hand. Its name and message are still revised, and it can still be
 *   discarded. It is started while it keeps someone to contact;
 * - a started campaign is run and stopped;
 * - until a campaign is stopped or ended, its daily cap changes;
 * - a stopped or ended campaign allows nothing.
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
            CampaignStatus::Draft => [CampaignIntent::ReviseCampaign, CampaignIntent::DiscardCampaign, CampaignIntent::FreezeCampaign, CampaignIntent::SetDailyCap],
            CampaignStatus::Frozen => [CampaignIntent::ReviseCampaign, CampaignIntent::DiscardCampaign, CampaignIntent::AddRecipients, CampaignIntent::RemoveRecipient, CampaignIntent::SetDailyCap, ...($hasRecipients ? [CampaignIntent::StartCampaign] : [])],
            CampaignStatus::Started => [CampaignIntent::RunCampaign, CampaignIntent::StopCampaign, CampaignIntent::SetDailyCap],
            CampaignStatus::Stopped, CampaignStatus::Ended => [],
        };
    }
}
