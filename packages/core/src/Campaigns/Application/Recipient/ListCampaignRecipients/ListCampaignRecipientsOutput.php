<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Recipient\ListCampaignRecipients;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ListCampaignRecipientsOutput extends OutputPort
{
    public function present(CampaignRecipients $recipients): void;
}
