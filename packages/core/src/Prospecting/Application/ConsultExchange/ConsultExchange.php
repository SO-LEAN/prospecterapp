<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

/**
 * The whole of an exchange, who noted it and who corrected it last, and what it offers at the moment of the reading.
 */
final readonly class ConsultExchange
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public \DateTimeImmutable $asOf,
    ) {
    }
}
