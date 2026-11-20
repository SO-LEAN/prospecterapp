<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * What a reading of a mailbox found: the messages added, by their id at the provider, the point of its history the
 * reading reached, and whether it reached the end of the history or stopped at the most it may list (ADR 0154).
 */
final readonly class MailboxChanges
{
    /**
     * @param list<string> $messageIds
     */
    public function __construct(
        public array $messageIds,
        public string $historyId,
        public bool $complete,
    ) {
    }
}
