<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Campaign\ConsultCampaign;

use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultCampaignOutput extends OutputPort
{
    /**
     * @param int $targeted       the count of contacts it targets now
     * @param int $processedToday its recipients processed today, on the member's clock
     */
    public function present(CampaignSheet $campaign, int $targeted, int $processedToday): void;
}
