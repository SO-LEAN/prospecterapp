<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * The intention exists, but the state of the aggregate does not offer it now.
 */
class NotPossibleInItsState extends BusinessRuleViolation
{
}
