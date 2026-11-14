<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\CheckMyConnections;

/**
 * Each provider I connected is asked whether it still grants the access I gave it.
 */
final readonly class CheckMyConnections
{
    public function __construct(
        public string $memberId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
