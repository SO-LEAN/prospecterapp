<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application;

/**
 * A client that an account created at a provider, as the answer gives it. It holds the client's id, never its secret
 * (ADR 0125). While the client has a topic, it also holds that topic and the service account that pushes the
 * notifications. The account's mailboxes notify Prospecter through that topic (ADR 0154).
 */
final readonly class AccountClient
{
    public function __construct(
        public string $provider,
        public string $clientId,
        public ?string $mailboxTopic = null,
        public ?string $mailboxPushAccount = null,
    ) {
    }
}
