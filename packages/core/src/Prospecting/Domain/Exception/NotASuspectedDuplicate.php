<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The organization is not suspected of being a duplicate of the named one. There is no suspicion to dismiss.
 */
final class NotASuspectedDuplicate extends NotPossibleInItsState
{
}
