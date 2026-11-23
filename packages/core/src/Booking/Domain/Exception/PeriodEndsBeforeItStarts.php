<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * An inactive period lasts at least one day. Its last day is its first day or a later one.
 */
final class PeriodEndsBeforeItStarts extends BusinessRuleViolation
{
    public static function refused(): self
    {
        return self::because('An inactive period ends on or after its first day', 'lastDay');
    }
}
