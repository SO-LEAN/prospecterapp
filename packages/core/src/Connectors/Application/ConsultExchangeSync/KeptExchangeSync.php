<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultExchangeSync;

use Prospecter\Core\Connectors\Application\ExchangeSync;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptExchangeSync implements ConsultExchangeSyncOutput
{
    public private(set) ?ExchangeSync $sync = null;

    public function present(?ExchangeSync $sync): void
    {
        $this->sync = $sync;
    }
}
