<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptExchange implements ConsultExchangeOutput
{
    public private(set) ExchangeDetail $exchange;

    public function present(ExchangeDetail $exchange): void
    {
        $this->exchange = $exchange;
    }
}
