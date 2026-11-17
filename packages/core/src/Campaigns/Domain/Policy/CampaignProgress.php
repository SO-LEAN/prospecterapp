<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Policy;

use Prospecter\Core\Campaigns\Domain\Model\CampaignStatus;

/**
 * How far a campaign went (ADR 0144): nobody is contacted while it is a draft, its target not frozen yet.
 */
final class CampaignProgress
{
    #[\NoDiscard]
    public static function contactedIn(CampaignStatus $status): int
    {
        return match ($status) {
            CampaignStatus::Draft => 0,
        };
    }
}
