<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Calendar\RetryPush;

final readonly class RetryPush
{
    public function __construct(
        public string $memberId,
        public string $exchangeId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
