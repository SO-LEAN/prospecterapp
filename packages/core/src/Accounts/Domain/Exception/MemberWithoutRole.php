<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A member works with at least one role beyond signing in.
 */
final class MemberWithoutRole extends BusinessRuleViolation
{
}
