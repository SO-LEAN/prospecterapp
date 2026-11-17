<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085): none for an unknown campaign.
 */
final class KeptPendingRecipients implements WhichRecipientsArePendingOutput
{
    public private(set) ?PendingRecipientsResponse $campaign = null;

    public function present(PendingRecipientsResponse $campaign): void
    {
        $this->campaign = $campaign;
    }
}
