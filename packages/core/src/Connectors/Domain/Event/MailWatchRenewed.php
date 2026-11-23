<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * The member's mailbox notifies Prospecter for another week (ADR 0154).
 */
final readonly class MailWatchRenewed extends MailboxToRead
{
}
