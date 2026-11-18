<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\RemoveMailboxNotifications;

/**
 * The mailboxes of the members of my account no longer notify Prospecter through a topic of my account: the import of
 * their e-mails is no longer offered (ADR 0154).
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
