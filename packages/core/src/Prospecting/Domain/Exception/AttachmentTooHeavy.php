<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A file attached to an exchange weighs at most 10 MB.
 */
final class AttachmentTooHeavy extends BusinessRuleViolation
{
}
