<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The member's e-mail import is off, or to reconnect. Nothing in their mailbox is read.
 */
final class MailImportNotActive extends NotPossibleInItsState
{
    public static function toCatchUp(): self
    {
        return self::because('The import of the e-mails is not active');
    }
}
