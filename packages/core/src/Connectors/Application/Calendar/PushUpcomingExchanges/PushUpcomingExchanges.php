<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Calendar\PushUpcomingExchanges;

final readonly class PushUpcomingExchanges
{
    public function __construct(
        public string $accountId,
        public string $memberId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
