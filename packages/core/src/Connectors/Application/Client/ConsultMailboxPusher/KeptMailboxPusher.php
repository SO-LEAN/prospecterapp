<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Client\ConsultMailboxPusher;

final class KeptMailboxPusher implements ConsultMailboxPusherOutput
{
    public private(set) ?string $pushAccount = null;

    public function present(?string $pushAccount): void
    {
        $this->pushAccount = $pushAccount;
    }
}
