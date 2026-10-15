<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\Shared;

/**
 * An intent the aggregate does not offer in its current state: the same rule removes its link.
 */
final class NotPossibleInItsState extends BusinessRuleViolation
{
}
