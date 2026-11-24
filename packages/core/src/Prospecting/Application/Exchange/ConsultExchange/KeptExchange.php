<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange;

/**
 * Keeps the answer unchanged, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptExchange implements ConsultExchangeOutput
{
    public private(set) ExchangeDetail $exchange;

    public function present(ExchangeDetail $exchange): void
    {
        $this->exchange = $exchange;
    }
}
