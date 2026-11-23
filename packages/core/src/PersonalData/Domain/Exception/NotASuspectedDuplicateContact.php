<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The contact is not suspected of being this contact's duplicate, so there is nothing to dismiss (ADR 0170).
 */
final class NotASuspectedDuplicateContact extends NotPossibleInItsState
{
}
