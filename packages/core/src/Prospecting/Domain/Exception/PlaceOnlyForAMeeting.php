<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Only a meeting is held somewhere: a call or a message has no place.
 */
final class PlaceOnlyForAMeeting extends BusinessRuleViolation
{
}
