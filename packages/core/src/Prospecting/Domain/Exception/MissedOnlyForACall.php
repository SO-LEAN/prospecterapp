<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Only a call goes through or not: a message is sent, a meeting is held.
 */
final class MissedOnlyForACall extends BusinessRuleViolation
{
}
