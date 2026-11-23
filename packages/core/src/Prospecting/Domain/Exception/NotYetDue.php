<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The planned exchange's time has not come. Nobody knows yet whether it takes place.
 */
final class NotYetDue extends NotPossibleInItsState
{
}
