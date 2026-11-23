<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultTimelineIntents;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultTimelineIntentsOutput extends OutputPort
{
    public function present(TimelineIntentsResponse $intents): void;
}
