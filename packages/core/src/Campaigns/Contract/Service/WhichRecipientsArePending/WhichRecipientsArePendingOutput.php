<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending;

interface WhichRecipientsArePendingOutput
{
    /** Not called for a campaign that the account does not have. */
    public function present(PendingRecipientsResponse $campaign): void;
}
