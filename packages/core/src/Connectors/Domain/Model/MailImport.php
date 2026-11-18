<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * Where the import of the e-mails of a member stands: off until they activate it, active while their mailbox notifies
 * Prospecter, to reconnect once the provider refused to renew its watch (ADR 0154).
 */
enum MailImport: string
{
    case Off = 'off';
    case Active = 'active';
    case ToReconnect = 'toReconnect';
}
