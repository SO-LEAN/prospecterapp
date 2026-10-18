<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * An account is opened with the corporate name of its organization.
 */
final class AccountWithoutCorporateName extends BusinessRuleViolation
{
}
