<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * Another command changed the data since this command read it. Writing it would erase that change without its
 * author knowing. The author reads the data again before changing it (ADR 0181).
 */
final class ChangedMeanwhile extends NotPossibleInItsState
{
    public static function sinceRead(\Throwable $detectedBy): self
    {
        return self::because('Someone else changed this since it was read', previous: $detectedBy);
    }
}
