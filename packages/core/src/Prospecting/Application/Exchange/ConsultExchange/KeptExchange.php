<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * Keeps the answer unchanged, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptExchange implements ConsultExchangeOutput
{
    public private(set) ExchangeDetail $exchange;
    public private(set) ?OrganizationSheet $organizationItself = null;

    public function present(ExchangeDetail $exchange, ?OrganizationSheet $organizationItself): void
    {
        $this->exchange = $exchange;
        $this->organizationItself = $organizationItself;
    }
}
