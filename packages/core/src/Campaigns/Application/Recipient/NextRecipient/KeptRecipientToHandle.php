<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Recipient\NextRecipient;

final class KeptRecipientToHandle implements NextRecipientOutput
{
    public private(set) ?RecipientToHandle $recipient = null;

    public function present(RecipientToHandle $recipient): void
    {
        $this->recipient = $recipient;
    }
}
