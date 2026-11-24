<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Calendar\RetryFailedPushes;

final readonly class RetryFailedPushes
{
    public function __construct(
        public string $memberId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
