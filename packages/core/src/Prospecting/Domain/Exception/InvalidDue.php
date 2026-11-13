<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A to-do is due on a day of the calendar, at an hour of that day when it is said.
 */
final class InvalidDue extends BusinessRuleViolation
{
}
