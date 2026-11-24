<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StopKeepingToDos;

final readonly class StopKeepingToDos
{
    public function __construct(
        public string $memberId,
        public string $organizationId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
