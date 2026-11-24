<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Campaign\ListCampaigns;

use Prospecter\Core\Campaigns\Application\CampaignSheet;

final class KeptCampaigns implements ListCampaignsOutput
{
    /**
     * @var list<CampaignSheet>
     */
    public private(set) array $campaigns = [];
    /**
     * @var array<string, int>
     */
    public private(set) array $targeted = [];

    public function present(array $campaigns, array $targeted): void
    {
        $this->campaigns = $campaigns;
        $this->targeted = $targeted;
    }
}
