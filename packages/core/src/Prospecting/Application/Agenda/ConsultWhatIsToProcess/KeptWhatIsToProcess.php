<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda\ConsultWhatIsToProcess;

/**
 * Keeps the answer unchanged, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptWhatIsToProcess implements ConsultWhatIsToProcessOutput
{
    public private(set) WhatIsToProcess $toProcess;

    public function present(WhatIsToProcess $toProcess): void
    {
        $this->toProcess = $toProcess;
    }
}
