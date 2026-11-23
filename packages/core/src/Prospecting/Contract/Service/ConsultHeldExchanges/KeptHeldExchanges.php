<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges;

/**
 * Keeps the answer as it is, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptHeldExchanges implements ConsultHeldExchangesOutput
{
    /**
     * @var list<HeldExchangeResponse>
     */
    public private(set) array $exchanges = [];

    public function present(array $exchanges): void
    {
        $this->exchanges = $exchanges;
    }
}
