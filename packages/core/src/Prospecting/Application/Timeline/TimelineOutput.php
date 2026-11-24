<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Timeline;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface TimelineOutput extends OutputPort
{
    public function present(Timeline $timeline): void;
}
