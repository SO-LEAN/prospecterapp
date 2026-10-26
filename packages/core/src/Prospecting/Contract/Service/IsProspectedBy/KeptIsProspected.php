<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\IsProspectedBy;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0084).
 */
final class KeptIsProspected implements IsProspectedByOutput
{
    public private(set) bool $isProspected;

    public function present(bool $isProspected): void
    {
        $this->isProspected = $isProspected;
    }
}
