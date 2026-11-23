<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Policy;

use Prospecter\Core\Accounts\Contract\Event\MemberRegistered;
use Prospecter\Core\PersonalData\Application\CollectTheMemberProfile\CollectTheMemberProfile;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When an administrator registers a member, the member gets an empty profile to fill in (ADR 0031, 0075).
 */
final readonly class CollectTheMemberProfilePolicy implements Policy
{
    public function __invoke(MemberRegistered $event): CollectTheMemberProfile
    {
        return new CollectTheMemberProfile(
            accountId: $event->accountId,
            administratorId: $event->administratorId,
            profileId: $event->profileId,
            executedAt: $event->occurredAt,
        );
    }
}
