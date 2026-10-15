<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\WriteObservations;

final readonly class WriteObservations
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public ?string $observations,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
