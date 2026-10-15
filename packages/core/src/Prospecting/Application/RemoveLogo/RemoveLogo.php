<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RemoveLogo;

final readonly class RemoveLogo
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
