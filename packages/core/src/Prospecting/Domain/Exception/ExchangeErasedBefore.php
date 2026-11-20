<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The exchange of the same fingerprint was erased by a member: it does not come back (ADR 0166).
 */
final class ExchangeErasedBefore extends NotPossibleInItsState
{
}
