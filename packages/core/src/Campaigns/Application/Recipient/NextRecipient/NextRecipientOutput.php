<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Recipient\NextRecipient;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface NextRecipientOutput extends OutputPort
{
    public function present(RecipientToHandle $recipient): void;
}
