<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

/**
 * The whole of an exchange, who noted it and who corrected it last.
 */
final readonly class ConsultExchange
{
    public function __construct(
        public string $prospectorId,
        public string $id,
    ) {
    }
}
