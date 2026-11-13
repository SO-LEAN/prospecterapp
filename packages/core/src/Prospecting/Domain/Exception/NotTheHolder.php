<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

/**
 * A planned exchange is in the agenda of the member who planned it: a colleague does not handle it for them.
 */
final class NotTheHolder extends NotAllowed
{
}
