<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\MarkExchangeMissed;

/**
 * The holder marks a planned exchange missed once its time has come: the contact did not come or answer.
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
