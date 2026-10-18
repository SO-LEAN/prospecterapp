<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The organization has no contact to remove.
 */
final class NoContactToRemove extends NotPossibleInItsState
{
}
