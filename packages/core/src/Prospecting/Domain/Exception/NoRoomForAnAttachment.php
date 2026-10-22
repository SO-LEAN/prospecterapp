<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The exchange already holds as many attachments as it may.
 */
final class NoRoomForAnAttachment extends NotPossibleInItsState
{
}
