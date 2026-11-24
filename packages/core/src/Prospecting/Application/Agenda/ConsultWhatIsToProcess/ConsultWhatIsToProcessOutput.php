<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda\ConsultWhatIsToProcess;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultWhatIsToProcessOutput extends OutputPort
{
    public function present(WhatIsToProcess $toProcess): void;
}
