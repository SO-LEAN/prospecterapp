<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultCalendar;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultCalendarOutput extends OutputPort
{
    public function present(Calendar $calendar): void;
}
