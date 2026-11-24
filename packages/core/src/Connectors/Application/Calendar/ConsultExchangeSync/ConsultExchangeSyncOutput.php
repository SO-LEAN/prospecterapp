<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Calendar\ConsultExchangeSync;

use Prospecter\Core\Connectors\Application\Calendar\ExchangeSync;
use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultExchangeSyncOutput extends OutputPort
{
    /**
     * @param ExchangeSync|null $sync null when the exchange is not pushed into my calendar
     */
    public function present(?ExchangeSync $sync): void;
}
