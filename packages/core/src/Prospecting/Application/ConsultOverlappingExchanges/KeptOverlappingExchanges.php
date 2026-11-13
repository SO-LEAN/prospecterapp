<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptOverlappingExchanges implements ConsultOverlappingExchangesOutput
{
    public private(set) OverlappingExchanges $overlapping;

    public function present(OverlappingExchanges $overlapping): void
    {
        $this->overlapping = $overlapping;
    }
}
