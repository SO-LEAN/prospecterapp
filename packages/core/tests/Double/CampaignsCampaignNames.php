<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Campaigns\Contract\Service\NameCampaigns\KeptCampaignNames;
use Prospecter\Core\Campaigns\Contract\Service\NameCampaigns\NameCampaigns;
use Prospecter\Core\Campaigns\Contract\Service\NameCampaigns\NameCampaignsService;
use Prospecter\Core\Prospecting\Domain\Port\CampaignNames;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;

/**
 * The API's anticorruption layer, in the scenarios (ADR 0045, 0089, 0145). A timeline names its exchanges' campaigns
 * as Campaigns currently names them. It asks once for all of them.
 */
final readonly class CampaignsCampaignNames implements CampaignNames
{
    public function __construct(private NameCampaignsService $nameCampaignsService)
    {
    }

    /**
     * @param list<CampaignId> $campaigns
     */
    public function of(array $campaigns, AccountId $accountId): array
    {
        if ([] === $campaigns) {
            return [];
        }

        $kept = new KeptCampaignNames();
        ($this->nameCampaignsService)(new NameCampaigns($accountId->value, array_map(static fn (CampaignId $campaign): string => $campaign->value, $campaigns)), $kept);

        return $kept->names;
    }
}
