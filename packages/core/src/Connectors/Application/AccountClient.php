<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application;

/**
 * A client an account made at a provider, as it is answered: its id, never its secret (ADR 0125), and the topic its
 * mailboxes notify Prospecter through with the account of service pushing them, while it has one (ADR 0154).
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
