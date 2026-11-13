<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A calendar is read a period at a time, at most what the grid of a month shows: six weeks.
 */
final class CalendarPeriodTooLong extends BusinessRuleViolation
{
}
