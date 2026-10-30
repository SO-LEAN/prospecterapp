<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface TimelineOutput extends OutputPort
{
    public function present(Timeline $timeline): void;
}
