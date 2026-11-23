<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * The caller can reach the aggregate but has no right to this intention.
 */
class NotAllowed extends BusinessRuleViolation
{
}
