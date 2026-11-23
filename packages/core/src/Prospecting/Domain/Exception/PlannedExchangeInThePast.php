<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A member plans an exchange for later, and logs one that already happened.
 */
final class PlannedExchangeInThePast extends BusinessRuleViolation
{
}
