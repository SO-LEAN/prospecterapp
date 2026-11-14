<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMyConnections;

final readonly class ConsultMyConnections
{
    public function __construct(
        public string $memberId,
    ) {
    }
}
