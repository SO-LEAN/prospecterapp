<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Contract\Event;

/**
 * An organization opened its account (ADR 0046). Its founder is its first member, under the profile the event names.
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
