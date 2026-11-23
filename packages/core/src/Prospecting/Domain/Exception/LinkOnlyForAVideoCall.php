<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Only a video call has a link to join it.
 */
final class LinkOnlyForAVideoCall extends BusinessRuleViolation
{
}
