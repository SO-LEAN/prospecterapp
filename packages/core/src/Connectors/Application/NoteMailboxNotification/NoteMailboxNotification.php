<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\NoteMailboxNotification;

/**
 * A mailbox of a member of the account notified Prospecter of a change, through the topic of the account: where its
 * history stands now (ADR 0154). No member at its origin: the provider pushed it.
 */
final readonly class NoteMailboxNotification
{
    public function __construct(
        public string $accountId,
        public string $provider,
        public string $mailbox,
        public string $historyId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
