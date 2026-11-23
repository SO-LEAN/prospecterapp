<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Slots start on the quarter hour, so a window starts and ends on a quarter hour.
 */
final class WindowOffTheQuarterHour extends BusinessRuleViolation
{
    public static function refused(): self
    {
        return self::because('A window starts and ends on a quarter hour', 'windows');
    }
}
