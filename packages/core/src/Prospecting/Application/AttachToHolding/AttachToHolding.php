<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AttachToHolding;

final readonly class AttachToHolding
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public string $holdingId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
