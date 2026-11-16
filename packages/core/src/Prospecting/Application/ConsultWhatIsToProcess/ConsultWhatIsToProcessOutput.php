<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultWhatIsToProcess;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultWhatIsToProcessOutput extends OutputPort
{
    public function present(WhatIsToProcess $toProcess): void;
}
