<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultMailboxPusher;

/**
 * Who pushes the notifications of the mailboxes of the members of an account: the account of service its topic names
 * (ADR 0154). No member at its origin: asked to trust a notification.
 */
final readonly class ConsultMailboxPusher
{
    public function __construct(
        public string $accountId,
        public string $provider,
    ) {
    }
}
