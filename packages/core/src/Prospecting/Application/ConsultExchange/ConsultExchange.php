<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

/**
 * The whole of an exchange, who noted it and who corrected it last, and what it offers at the moment of the reading;
 * read just after its move, from the moment it was moved from, it offers then to move it back there while that moment
 * is to come (ADR 0135).
 */
final readonly class ConsultExchange
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public \DateTimeImmutable $asOf,
        public ?\DateTimeImmutable $movedFrom = null,
    ) {
    }
}
