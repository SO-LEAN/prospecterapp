<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges;

/**
 * Asks who holds these exchanges of an account, their status, when, how and where they are held, and with whom
 * (ADR 0089, 0127). The query asks about a set. A single exchange is a set of one.
 */
final readonly class ConsultHeldExchanges
{
    /**
     * @param list<string> $exchangeIds
     */
    public function __construct(
        public string $accountId,
        public array $exchangeIds,
    ) {
    }
}
