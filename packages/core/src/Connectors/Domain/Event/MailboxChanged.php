<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * The member's mailbox notified Prospecter of a change. Its e-mails are to be read (ADR 0154).
 */
final readonly class MailboxChanged extends ConnectionEvent
{
}
