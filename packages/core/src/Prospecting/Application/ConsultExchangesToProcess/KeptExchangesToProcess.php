<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchangesToProcess;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptExchangesToProcess implements ConsultExchangesToProcessOutput
{
    public private(set) ExchangesToProcess $toProcess;

    public function present(ExchangesToProcess $toProcess): void
    {
        $this->toProcess = $toProcess;
    }
}
