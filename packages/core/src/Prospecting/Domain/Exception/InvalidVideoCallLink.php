<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A member joins a video call through a web address that a browser opens.
 */
final class InvalidVideoCallLink extends BusinessRuleViolation
{
}
