<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\MarkExchangeMissed;

/**
 * Marks missed a planned exchange whose time has come, by its holder: the contact did not come or answer.
 */
final readonly class MarkExchangeMissed
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
