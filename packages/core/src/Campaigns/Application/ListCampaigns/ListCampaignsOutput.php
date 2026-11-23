<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ListCampaigns;

use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ListCampaignsOutput extends OutputPort
{
    /**
     * @param list<CampaignSheet> $campaigns newest first
     * @param array<string, int>  $targeted  the count of contacts each campaign targets, by campaign id
     */
    public function present(array $campaigns, array $targeted): void;
}
