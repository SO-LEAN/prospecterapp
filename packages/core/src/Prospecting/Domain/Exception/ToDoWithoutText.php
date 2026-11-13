<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A to-do says what is to do.
 */
final class ToDoWithoutText extends BusinessRuleViolation
{
}
