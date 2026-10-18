<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * An exchange is noted once it happened.
 */
final class ExchangeInTheFuture extends BusinessRuleViolation
{
}
