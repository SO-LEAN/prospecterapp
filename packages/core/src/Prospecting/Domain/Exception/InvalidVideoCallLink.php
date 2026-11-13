<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A video call is joined by a web address, opened in a browser.
 */
final class InvalidVideoCallLink extends BusinessRuleViolation
{
}
