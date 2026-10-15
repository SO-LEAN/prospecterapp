<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * Who asks may reach the aggregate, but has no right to this intention.
 */
final class NotAllowed extends BusinessRuleViolation
{
}
