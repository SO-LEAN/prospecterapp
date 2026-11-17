<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\Prospecting\Domain\Port\CampaignNames;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;

/**
 * Names the campaigns the exchanges of a timeline were noted in, as Campaigns names them now, asked once for the
 * slice: an exchange keeps only the identity of its campaign (ADR 0145).
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
