<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * The state of a member's e-mail import. It is off until the member activates it. It is active while their mailbox
 * notifies Prospecter. It is to reconnect once the provider refused to renew the mailbox's watch (ADR 0154).
 */
enum MailImport: string
{
    case Off = 'off';
    case Active = 'active';
    case ToReconnect = 'toReconnect';
}
