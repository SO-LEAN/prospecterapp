<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * From now on, the member's mailbox notifies Prospecter of their new e-mails (ADR 0154).
 */
final readonly class MailImportStarted extends ConnectionEvent
{
}
