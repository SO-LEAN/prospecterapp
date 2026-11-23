<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultExchangeSync;

use Prospecter\Core\Connectors\Application\ExchangeSync;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultExchangeSyncOutput extends OutputPort
{
    /**
     * @param ExchangeSync|null $sync null when the exchange is not pushed into my calendar
     */
    public function present(?ExchangeSync $sync): void;
}
