<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A planned exchange lasts long enough for a call, never longer than a working day.
 */
final class DurationOutOfRange extends BusinessRuleViolation
{
}
