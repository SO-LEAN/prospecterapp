<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * An organization is named by its corporate name or its email, at least one.
 */
final class OrganizationWithoutNameNorEmail extends BusinessRuleViolation
{
}
