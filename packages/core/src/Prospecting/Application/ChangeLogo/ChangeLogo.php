<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ChangeLogo;

final readonly class ChangeLogo
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public \SplFileInfo $logo,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
