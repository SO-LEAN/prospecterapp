<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMailboxPusher;

/**
 * Gets the service account that pushes the notifications of an account's mailboxes. The account's topic names it
 * (ADR 0154). No member asks for it: Prospecter asks for it to trust a notification.
 */
final readonly class ConsultMailboxPusher
{
    public function __construct(
        public string $accountId,
        public string $provider,
    ) {
    }
}
