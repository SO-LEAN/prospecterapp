<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\RemoveClient;

final readonly class RemoveClient
{
    public function __construct(
        public string $memberId,
        public string $provider,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
