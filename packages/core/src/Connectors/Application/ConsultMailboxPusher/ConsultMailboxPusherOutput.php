<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMailboxPusher;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultMailboxPusherOutput extends OutputPort
{
    /**
     * @param string|null $pushAccount the service account, null while the account has no topic
     */
    public function present(?string $pushAccount): void;
}
