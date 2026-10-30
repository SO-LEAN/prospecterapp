<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultExchangeOutput extends OutputPort
{
    public function present(ExchangeDetail $exchange): void;
}
