<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * What the command changes was changed by another one since it read it: written, it would erase that change unknown
 * to its author, who reads it again before changing it (ADR 0181).
 */
final class ChangedMeanwhile extends NotPossibleInItsState
{
    public static function sinceRead(\Throwable $detectedBy): self
    {
        return self::because('Changed meanwhile, since it was read', previous: $detectedBy);
    }
}
