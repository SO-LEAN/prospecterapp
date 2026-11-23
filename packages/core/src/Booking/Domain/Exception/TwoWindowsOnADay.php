<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A weekday holds one window at most.
 */
final class TwoWindowsOnADay extends BusinessRuleViolation
{
    public static function refused(): self
    {
        return self::because('A day can hold only one window', 'windows');
    }
}
