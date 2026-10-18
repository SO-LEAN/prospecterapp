<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The organization has no logo to remove.
 */
final class NoLogoToRemove extends NotPossibleInItsState
{
}
