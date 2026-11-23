<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * A member erased the exchange with the same fingerprint. It does not come back (ADR 0166).
 */
final class ExchangeErasedBefore extends NotPossibleInItsState
{
}
