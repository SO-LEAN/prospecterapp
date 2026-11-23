<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts;

/**
 * Asks for the facts of the exchanges an account keeps, the most recently logged first, one slice after a cursor
 * (ADR 0049). Another context needs them to count the exchanges again from the state.
 */
final readonly class ListExchangeFacts
{
    public function __construct(
        public string $accountId,
        public ?string $after,
        public int $max,
    ) {
    }
}
