<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConfigureMailboxNotifications;

/**
 * The topic of my account at the provider its mailboxes notify Prospecter through, and the account of service
 * pushing those notifications to Prospecter (ADR 0154): the members may import their e-mails from now on.
 */
final readonly class ConfigureMailboxNotifications
{
    public function __construct(
        public string $memberId,
        public string $provider,
        public string $topic,
        public string $pushAccount,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
