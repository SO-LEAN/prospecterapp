<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The time of the planned exchange has not come: nobody knows yet whether it takes place.
 */
final class NotYetDue extends NotPossibleInItsState
{
}
