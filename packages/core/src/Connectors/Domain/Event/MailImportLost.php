<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * The provider refused to renew the watch on the member's mailbox. The import is to reconnect (ADR 0154).
 */
final readonly class MailImportLost extends ConnectionEvent
{
}
