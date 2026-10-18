<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The person is no contact of the organization.
 */
final class NotAContact extends NotPossibleInItsState
{
}
