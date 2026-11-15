<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges;

/**
 * Who holds these exchanges of an account, where they stand, when, how and where they are held, with whom (ADR 0089,
 * 0127): asked about a set, a single exchange being a set of one.
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
