<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\Prospecting\Domain\Model\CampaignId;
use Prospecter\Core\Prospecting\Domain\Port\CampaignNames;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Names the campaigns that a timeline's exchanges were logged in, with the names Campaigns gives them now. It asks
 * Campaigns once per slice. An exchange keeps only its campaign's identity (ADR 0145).
 */
final readonly class CampaignsOfTimeline
{
    public function __construct(private CampaignNames $campaignNames)
    {
    }

    #[\NoDiscard]
    public function named(Timeline $timeline, AccountId $accountId): Timeline
    {
        $campaigns = array_map(static fn (string $id): CampaignId => new CampaignId($id), $timeline->campaignIds());

        return [] === $campaigns ? $timeline : $timeline->withCampaignsNamed($this->campaignNames->of($campaigns, $accountId));
    }
}
