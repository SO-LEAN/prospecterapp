<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Policy;

use Prospecter\Core\Connectors\Application\MailImport\ImportMail\ImportMail;
use Prospecter\Core\Connectors\Domain\Event\MailboxMessageAdded;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When a message is added to a member's mailbox, it is imported (ADR 0154).
 */
final readonly class ImportMailPolicy implements Policy
{
    public function __invoke(MailboxMessageAdded $event): ImportMail
    {
        return new ImportMail(
            accountId: $event->accountId->value,
            memberId: $event->by->value,
            messageId: $event->messageId,
            executedAt: $event->occurredAt,
        );
    }
}
