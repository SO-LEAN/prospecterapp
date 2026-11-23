<?php

declare(strict_types=1);

namespace Fixtures\Cache;

final readonly class SendInvitation
{
    public function __construct(
        public string $accountId,
        public string $email,
        public string $phoneNumber,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
