<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Policy;

use Prospecter\Core\Accounts\Contract\Event\AccountOpened;
use Prospecter\Core\PersonalData\Application\CollectTheFounderProfile\CollectTheFounderProfile;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When an account opens, its founder gets an empty profile to fill in (ADR 0031, 0064).
 */
final readonly class CollectTheFounderProfilePolicy implements Policy
{
    public function __invoke(AccountOpened $event): CollectTheFounderProfile
    {
        return new CollectTheFounderProfile(
            accountId: $event->accountId,
            founderId: $event->founderId,
            profileId: $event->founderProfileId,
            executedAt: $event->occurredAt,
        );
    }
}
