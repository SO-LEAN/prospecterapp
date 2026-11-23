<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A to-do is due on a calendar day, and at an hour of that day when one is given.
 */
final class InvalidDue extends BusinessRuleViolation
{
}
