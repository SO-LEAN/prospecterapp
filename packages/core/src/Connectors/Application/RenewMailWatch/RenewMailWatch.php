<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\RenewMailWatch;

/**
 * The watch of the mailbox of a member renewed, as the platform does each day for every import active, a watch
 * lasting a week (ADR 0154): no member at its origin.
 */
final readonly class RenewMailWatch
{
    public function __construct(
        public string $memberId,
        public string $provider,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
