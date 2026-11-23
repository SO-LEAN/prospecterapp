<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The contact or the organization has no e-mail. The mailbox has nothing to search for.
 */
final class NoAddressToReimport extends NotPossibleInItsState
{
    public static function ofTheCorrespondent(): self
    {
        return self::because('No address to reimport the e-mails exchanged with');
    }
}
