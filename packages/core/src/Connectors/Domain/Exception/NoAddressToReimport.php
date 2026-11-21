<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * A contact, or an organization, without an e-mail: the mailbox has nothing to search them by.
 */
final class NoAddressToReimport extends NotPossibleInItsState
{
    public static function ofTheCorrespondent(): self
    {
        return self::because('No address to reimport the e-mails exchanged with');
    }
}
