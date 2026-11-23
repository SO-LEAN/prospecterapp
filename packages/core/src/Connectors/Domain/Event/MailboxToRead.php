<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * The member's mailbox may hold messages that Prospecter has not read (ADR 0154). The mailbox notified a change, or
 * its watch was renewed. The daily renewal catches up a lost notification.
 */
abstract readonly class MailboxToRead extends ConnectionEvent
{
}
