<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * A campaign's recipient gets one exchange in that campaign. A recipient already processed or dismissed no longer needs
 * handling.
 */
final class RecipientAlreadyProcessed extends NotPossibleInItsState
{
    public static function once(): self
    {
        return self::because('This recipient is already processed');
    }
}
