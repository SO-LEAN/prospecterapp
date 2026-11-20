<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * An exchange of the same fingerprint is already noted in the account: it is answered, not noted again (ADR 0166).
 */
final class ExchangeAlreadyNoted extends NotPossibleInItsState
{
}
