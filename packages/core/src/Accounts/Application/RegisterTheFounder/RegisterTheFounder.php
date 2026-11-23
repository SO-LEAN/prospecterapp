<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\RegisterTheFounder;

final readonly class RegisterTheFounder
{
    public function __construct(
        public string $accountId,
        public string $founderId,
        public string $founderProfileId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
