<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Policy;

use Prospecter\Core\Connectors\Application\ReadMailboxHistory\ReadMailboxHistory;
use Prospecter\Core\Connectors\Domain\Event\MailboxChanged;
use Prospecter\Core\Connectors\Domain\Event\MailWatchRenewed;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When a member's mailbox notifies a change, or when its watch is renewed, its history is read (ADR 0154).
 */
final readonly class ReadMailboxHistoryPolicy implements Policy
{
    public function __invoke(MailboxChanged|MailWatchRenewed $event): ReadMailboxHistory
    {
        return new ReadMailboxHistory($event->accountId->value, $event->by->value, $event->occurredAt);
    }
}
