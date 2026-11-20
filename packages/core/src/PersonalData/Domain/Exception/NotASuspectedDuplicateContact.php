<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The contact is not suspected of being a duplicate of this one: nothing to say it is none of (ADR 0170).
 */
final class NotASuspectedDuplicateContact extends NotPossibleInItsState
{
}
