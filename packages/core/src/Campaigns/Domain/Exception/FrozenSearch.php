<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Its recipients kept, a frozen campaign no longer follows its channel nor its tags: they stay as it was frozen.
 */
final class FrozenSearch extends BusinessRuleViolation
{
    public static function unchanged(): self
    {
        return self::because('The search of a frozen campaign does not change', 'tags');
    }
}
