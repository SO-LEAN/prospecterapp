<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * The member's mailbox no longer notifies Prospecter of their e-mails (ADR 0154).
 */
final readonly class MailImportStopped extends ConnectionEvent
{
}
