<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchangesToProcess;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultExchangesToProcessOutput extends OutputPort
{
    public function present(ExchangesToProcess $toProcess): void;
}
