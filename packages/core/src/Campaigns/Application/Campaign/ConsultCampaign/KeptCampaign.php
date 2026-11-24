<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Campaign\ConsultCampaign;

use Prospecter\Core\Campaigns\Application\CampaignSheet;

final class KeptCampaign implements ConsultCampaignOutput
{
    public private(set) ?CampaignSheet $campaign = null;
    public private(set) int $targeted = 0;
    public private(set) int $processedToday = 0;

    public function present(CampaignSheet $campaign, int $targeted, int $processedToday): void
    {
        $this->campaign = $campaign;
        $this->targeted = $targeted;
        $this->processedToday = $processedToday;
    }
}
