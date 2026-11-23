<?php

declare(strict_types=1);

namespace App\Prospecting\AntiCorruption;

use Prospecter\Core\Campaigns\Contract\Service\NameCampaigns\KeptCampaignNames;
use Prospecter\Core\Campaigns\Contract\Service\NameCampaigns\NameCampaigns;
use Prospecter\Core\Campaigns\Contract\Service\NameCampaigns\NameCampaignsService;
use Prospecter\Core\Prospecting\Domain\Model\CampaignId;
use Prospecter\Core\Prospecting\Domain\Port\CampaignNames;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Anticorruption layer (ADR 0045, 0089, 0145). A timeline names its exchanges' campaigns as Campaigns currently names
 * them. It asks Campaigns once for all of them.
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
