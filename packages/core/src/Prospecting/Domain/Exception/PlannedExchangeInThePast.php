<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * An exchange is planned for later: one that already happened is noted.
 */
final class PlannedExchangeInThePast extends BusinessRuleViolation
{
}
