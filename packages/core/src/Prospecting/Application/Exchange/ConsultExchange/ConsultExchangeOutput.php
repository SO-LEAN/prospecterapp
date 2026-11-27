<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultExchangeOutput extends OutputPort
{
    public function present(ExchangeDetail $exchange): void;
}
