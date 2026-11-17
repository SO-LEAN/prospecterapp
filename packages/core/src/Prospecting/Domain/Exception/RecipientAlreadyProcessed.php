<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * A recipient of a campaign gets one exchange of it: processed or dismissed already, it is no longer to handle.
 */
final class RecipientAlreadyProcessed extends NotPossibleInItsState
{
    public static function once(): self
    {
        return self::because('This recipient is already processed');
    }
}
