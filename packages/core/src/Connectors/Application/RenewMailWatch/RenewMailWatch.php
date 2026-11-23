<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\RenewMailWatch;

/**
 * Renews a member's mailbox watch. The platform renews every active import's watch each day, because a watch lasts a
 * week (ADR 0154). No member sends this command.
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
