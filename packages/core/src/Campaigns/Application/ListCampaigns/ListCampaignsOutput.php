<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ListCampaigns;

use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ListCampaignsOutput extends OutputPort
{
    /**
     * @param list<CampaignSheet> $campaigns the newest first
     * @param array<string, int>  $targeted  the contacts each targets, by campaign identity
     */
    public function present(array $campaigns, array $targeted): void;
}
