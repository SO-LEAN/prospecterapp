<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A member reads a calendar one period at a time. A period lasts six weeks at most, what a month's grid shows.
 */
final class CalendarPeriodTooLong extends BusinessRuleViolation
{
}
