<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * The intention exists, but the aggregate's current state does not allow it.
 */
class NotPossibleInItsState extends BusinessRuleViolation
{
}
