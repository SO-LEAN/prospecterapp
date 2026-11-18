<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

/**
 * A contact to attach is the member's who met them until attached: a colleague neither plans nor notes with them.
 */
final class ContactToAttachOfAnother extends NotAllowed
{
}
