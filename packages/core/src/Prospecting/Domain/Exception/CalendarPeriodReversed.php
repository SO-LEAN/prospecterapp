<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A calendar period ends after it starts.
 */
final class CalendarPeriodReversed extends BusinessRuleViolation
{
}
