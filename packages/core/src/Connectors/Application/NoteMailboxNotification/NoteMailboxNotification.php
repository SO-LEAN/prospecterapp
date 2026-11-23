<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\NoteMailboxNotification;

/**
 * The mailbox of one of the account's members notified Prospecter of a change, through the account's topic. The
 * notification says where the mailbox history stands now (ADR 0154). No member sends it: the provider pushed it.
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
