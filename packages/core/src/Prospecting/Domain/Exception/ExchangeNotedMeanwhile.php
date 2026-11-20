<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * Two notings of the same exchange at once: the second one, refused by the safety net of NotedOnce, does not know the
 * exchange noted first (ADR 0166).
 */
final class ExchangeNotedMeanwhile extends NotPossibleInItsState
{
    public static function byAnother(?\Throwable $detectedBy = null): self
    {
        return self::because('Exchange noted meanwhile', previous: $detectedBy);
    }
}
