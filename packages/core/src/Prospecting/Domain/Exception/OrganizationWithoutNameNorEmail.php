<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * An organization needs a corporate name or an email to name it, or both.
 */
final class OrganizationWithoutNameNorEmail extends BusinessRuleViolation
{
}
