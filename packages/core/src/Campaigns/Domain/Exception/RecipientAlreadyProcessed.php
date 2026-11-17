<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * A recipient is handled once: processed by an exchange, dismissed, or erased, it is no longer to handle.
 */
final class RecipientAlreadyProcessed extends NotPossibleInItsState
{
    public static function once(): self
    {
        return self::because('This recipient is already processed');
    }
}
