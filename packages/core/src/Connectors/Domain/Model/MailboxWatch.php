<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * What the provider answers when it starts, or renews, watching a mailbox: where its history stands at that moment,
 * the import starting from there, and until when it notifies its changes (ADR 0154).
 */
final readonly class MailboxWatch
{
    public function __construct(
        public string $historyId,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
