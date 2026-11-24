<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StopKeepingExchanges;

final readonly class StopKeepingExchanges
{
    public function __construct(
        public string $memberId,
        public string $organizationId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
