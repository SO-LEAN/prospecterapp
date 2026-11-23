<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\CheckMyConnections;

/**
 * Asks each provider that I connected whether it still grants the access that I gave it.
 */
final readonly class CheckMyConnections
{
    public function __construct(
        public string $memberId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
