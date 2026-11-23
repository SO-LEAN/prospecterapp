<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The same exchange was logged twice at once. `NotedOnce`'s safety net refuses the second one, which does not know
 * the exchange logged first (ADR 0166).
 */
final class ExchangeNotedMeanwhile extends NotPossibleInItsState
{
    public static function byAnother(?\Throwable $detectedBy = null): self
    {
        return self::because('This exchange was logged in the meantime', previous: $detectedBy);
    }
}
