<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A frozen campaign keeps its recipients. Its channel and tags no longer change: they stay as they were when it was
 * frozen.
 */
final class FrozenSearch extends BusinessRuleViolation
{
    public static function unchanged(): self
    {
        return self::because('The search of a frozen campaign does not change', 'tags');
    }
}
