<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Calendar\ConsultExchangeSync;

final readonly class ConsultExchangeSync
{
    public function __construct(
        public string $memberId,
        public string $exchangeId,
        public \DateTimeImmutable $readAt,
    ) {
    }
}
