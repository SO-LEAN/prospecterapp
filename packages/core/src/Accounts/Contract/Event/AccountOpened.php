<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Event;

/**
 * An organization opened its account (ADR 0046): its founder is its first member, under the profile named.
 */
final readonly class AccountOpened
{
    public function __construct(
        public string $accountId,
        public string $founderId,
        public string $founderProfileId,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
