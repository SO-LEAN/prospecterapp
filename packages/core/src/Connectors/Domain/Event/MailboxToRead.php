<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * The mailbox of the member may hold messages Prospecter has not read: it notified a change, or its watch was renewed,
 * the daily renewal catching up a notification lost (ADR 0154).
 */
abstract readonly class MailboxToRead extends ConnectionEvent
{
}
