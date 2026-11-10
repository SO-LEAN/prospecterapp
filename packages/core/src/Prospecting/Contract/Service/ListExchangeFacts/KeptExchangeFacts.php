<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptExchangeFacts implements ListExchangeFactsOutput
{
    /**
     * @var list<ExchangeFactsResponse>
     */
    public private(set) array $facts = [];
    public private(set) ?string $next = null;

    /**
     * @param list<ExchangeFactsResponse> $facts
     */
    public function present(array $facts, ?string $next): void
    {
        $this->facts = $facts;
        $this->next = $next;
    }
}
