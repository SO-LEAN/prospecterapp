<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A file joined to an exchange weighs 10 MB at most.
 */
final class AttachmentTooHeavy extends BusinessRuleViolation
{
}
