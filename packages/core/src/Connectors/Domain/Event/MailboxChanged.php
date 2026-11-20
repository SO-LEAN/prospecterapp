<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * The mailbox of the member notified Prospecter of a change, its e-mails to read (ADR 0154).
 */
final readonly class MailboxChanged extends MailboxToRead
{
}
