<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Policy;

use Prospecter\Core\Campaigns\Domain\Model\CampaignIntent;
use Prospecter\Core\Campaigns\Domain\Model\CampaignStatus;

/**
 * What a campaign offers in its state (ADR 0144): a draft is revised and discarded.
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
            CampaignStatus::Draft => CampaignIntent::cases(),
        };
    }
}
