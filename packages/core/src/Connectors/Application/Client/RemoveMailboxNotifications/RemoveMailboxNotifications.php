<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Client\RemoveMailboxNotifications;

/**
 * My account's mailboxes no longer notify Prospecter through a topic of my account. The members are no longer offered
 * the e-mail import (ADR 0154).
 */
final readonly class RemoveMailboxNotifications
{
    public function __construct(
        public string $memberId,
        public string $provider,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
