<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Event;

/**
 * An administrator registered a member in their account (ADR 0046), under the profile named.
 */
final readonly class MemberRegistered
{
    public function __construct(
        public string $accountId,
        public string $administratorId,
        public string $memberId,
        public string $profileId,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
