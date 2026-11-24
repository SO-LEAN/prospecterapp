<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda\ConsultMyDay;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultMyDayOutput extends OutputPort
{
    public function present(MyDay $myDay): void;
}
