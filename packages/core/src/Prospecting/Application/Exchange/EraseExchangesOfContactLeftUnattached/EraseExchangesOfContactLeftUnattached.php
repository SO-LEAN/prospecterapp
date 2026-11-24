<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\EraseExchangesOfContactLeftUnattached;

final readonly class EraseExchangesOfContactLeftUnattached
{
    public function __construct(
        public string $accountId,
        public string $holderId,
        public string $contactId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
