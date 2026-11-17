<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultCampaign;

use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultCampaignOutput extends OutputPort
{
    /**
     * @param int $targeted the contacts it targets now
     */
    public function present(CampaignSheet $campaign, int $targeted): void;
}
