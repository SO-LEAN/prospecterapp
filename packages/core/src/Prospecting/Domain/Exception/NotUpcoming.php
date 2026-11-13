<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The exchange is no longer to come: done, missed or cancelled, it is not handled again.
 */
final class NotUpcoming extends NotPossibleInItsState
{
}
