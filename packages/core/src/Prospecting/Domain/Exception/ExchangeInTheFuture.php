<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A member logs an exchange once it has happened.
 */
final class ExchangeInTheFuture extends BusinessRuleViolation
{
}
