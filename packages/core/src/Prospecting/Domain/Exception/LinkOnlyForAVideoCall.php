<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Only a video call is joined by a link.
 */
final class LinkOnlyForAVideoCall extends BusinessRuleViolation
{
}
