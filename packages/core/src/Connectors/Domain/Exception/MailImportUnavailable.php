<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The client of the account has no topic its mailboxes would notify Prospecter through (ADR 0154).
 */
final class MailImportUnavailable extends NotPossibleInItsState
{
    public static function withoutTopic(): self
    {
        return self::because('The import of the e-mails is not available');
    }
}
