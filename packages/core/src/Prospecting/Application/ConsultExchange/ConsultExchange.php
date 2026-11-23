<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchange;

/**
 * Reads a whole exchange: who logged it, who corrected it last, and what it offers at the reading moment. A reader
 * may read it just after a move and give the moment it was moved from. The exchange then offers to move it back
 * there while that moment is still to come (ADR 0135).
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
