<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Connection\ConsultMyConnections;

final readonly class ConsultMyConnections
{
    public function __construct(
        public string $memberId,
        public \DateTimeImmutable $readAt,
    ) {
    }
}
