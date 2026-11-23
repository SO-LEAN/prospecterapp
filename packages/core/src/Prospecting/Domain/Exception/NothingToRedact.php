<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The exchange has nothing to redact. Its contact was not forgotten, or the exchange was already redacted.
 */
final class NothingToRedact extends NotPossibleInItsState
{
}
