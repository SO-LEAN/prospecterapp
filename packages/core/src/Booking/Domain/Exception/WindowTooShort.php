<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A window ends at least 15 minutes after it starts, so it holds at least one slot.
 */
final class WindowTooShort extends BusinessRuleViolation
{
    public static function holdsNoSlot(): self
    {
        return self::because('A window holds at least one slot of 15 minutes', 'windows');
    }
}
