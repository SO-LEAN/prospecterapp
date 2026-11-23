<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The account already logged an exchange with the same fingerprint. Prospecter answers with that exchange and does not
 * log it again (ADR 0166).
 */
final class ExchangeAlreadyNoted extends NotPossibleInItsState
{
}
