<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Profile\CollectTheFounderProfile;

final readonly class CollectTheFounderProfile
{
    public function __construct(
        public string $accountId,
        public string $founderId,
        public string $profileId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
