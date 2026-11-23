<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The organization is not attached to any holding.
 */
final class NoHoldingToDetachFrom extends NotPossibleInItsState
{
}
