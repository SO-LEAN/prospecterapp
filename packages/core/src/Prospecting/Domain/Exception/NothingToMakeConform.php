<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The exchange has nothing to make conform: its contact was not forgotten, or it was made conform already.
 */
final class NothingToMakeConform extends NotPossibleInItsState
{
}
