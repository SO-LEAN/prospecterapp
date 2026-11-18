<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * The mailbox of the member no longer notifies Prospecter of their e-mails (ADR 0154).
 */
final readonly class MailImportStopped extends ConnectionEvent
{
}
