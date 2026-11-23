<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\RenderCampaignMessage;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface RenderCampaignMessageOutput extends OutputPort
{
    public function present(CampaignMessage $message): void;
}
