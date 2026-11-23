<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * An account needs its organization's corporate name to open.
 */
final class AccountWithoutCorporateName extends BusinessRuleViolation
{
}
