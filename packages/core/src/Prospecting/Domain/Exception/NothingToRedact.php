<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The exchange has nothing to redact: its contact was not forgotten, or it was redacted already.
 */
final class NothingToRedact extends NotPossibleInItsState
{
}
