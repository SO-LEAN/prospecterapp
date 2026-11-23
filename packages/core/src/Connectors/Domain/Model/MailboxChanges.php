<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * What a mailbox reading found. It lists the added messages by their id at the provider. It holds the history point
 * that the reading reached. It says whether the reading reached the history's end, or stopped at the most messages it
 * may list (ADR 0154).
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
