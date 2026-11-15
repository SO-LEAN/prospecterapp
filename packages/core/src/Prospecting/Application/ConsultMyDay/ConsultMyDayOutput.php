<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultMyDay;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultMyDayOutput extends OutputPort
{
    public function present(MyDay $myDay): void;
}
