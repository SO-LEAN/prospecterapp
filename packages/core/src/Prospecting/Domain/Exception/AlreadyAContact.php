<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The person is a contact of the organization already.
 */
final class AlreadyAContact extends NotPossibleInItsState
{
}
