<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\PushExchange;

final readonly class PushExchange
{
    public function __construct(
        public string $accountId,
        public string $exchangeId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
