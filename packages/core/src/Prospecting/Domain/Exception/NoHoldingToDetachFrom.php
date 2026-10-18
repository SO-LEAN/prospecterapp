<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The organization is attached to no holding.
 */
final class NoHoldingToDetachFrom extends NotPossibleInItsState
{
}
