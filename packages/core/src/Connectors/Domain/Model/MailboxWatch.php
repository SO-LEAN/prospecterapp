<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * What the provider answers when it starts or renews a mailbox's watch. It gives the point where the mailbox history
 * stands at that moment, and the import starts from there. It also gives the date until which the provider notifies
 * the mailbox's changes (ADR 0154).
 */
final readonly class MailboxWatch
{
    public function __construct(
        public string $historyId,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
