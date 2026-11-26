<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Recipient\ListCampaignRecipients;

/**
 * Keeps the answer as it is, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptCampaignRecipients implements ListCampaignRecipientsOutput
{
    public private(set) ?CampaignRecipients $recipients = null;

    public function present(CampaignRecipients $recipients): void
    {
        $this->recipients = $recipients;
    }
}
