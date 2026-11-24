<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Client\ConfigureMailboxNotifications;

/**
 * I name my account's topic at the provider. The account's mailboxes notify Prospecter through it. I also name the
 * service account that pushes those notifications to Prospecter (ADR 0154). From now on, the members may import
 * their e-mails.
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
