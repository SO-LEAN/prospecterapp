<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\EraseExchangeLeftWithoutOrganization;

final readonly class EraseExchangeLeftWithoutOrganization
{
    public function __construct(
        public string $memberId,
        public string $exchangeId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
