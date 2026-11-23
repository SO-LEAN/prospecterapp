<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CollectTheMemberProfile;

final readonly class CollectTheMemberProfile
{
    public function __construct(
        public string $accountId,
        public string $administratorId,
        public string $profileId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
