<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DetachFromHolding;

final readonly class DetachFromHolding
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
