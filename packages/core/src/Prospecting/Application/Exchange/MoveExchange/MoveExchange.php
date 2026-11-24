<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\MoveExchange;

/**
 * The holder moves a planned exchange to another moment to come. It takes the given duration, or keeps its own when
 * none is given (ADR 0119).
 */
final readonly class MoveExchange
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public \DateTimeImmutable $plannedAt,
        public \DateTimeImmutable $executedAt,
        public ?int $durationInMinutes = null,
    ) {
    }
}
