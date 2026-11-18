<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * The mailbox of the member notifies Prospecter of their new e-mails from now on (ADR 0154).
 */
final readonly class MailImportStarted extends ConnectionEvent
{
}
