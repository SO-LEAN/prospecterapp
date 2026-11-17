<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\RenderCampaignMessage;

final class KeptCampaignMessage implements RenderCampaignMessageOutput
{
    public private(set) ?CampaignMessage $message = null;

    public function present(CampaignMessage $message): void
    {
        $this->message = $message;
    }
}
