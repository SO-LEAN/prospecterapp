<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultOverlappingExchangesOutput extends OutputPort
{
    public function present(OverlappingExchanges $overlapping): void;
}
