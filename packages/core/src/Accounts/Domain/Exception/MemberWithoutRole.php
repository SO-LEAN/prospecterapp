<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A member needs at least one role besides the right to sign in.
 */
final class MemberWithoutRole extends BusinessRuleViolation
{
}
