<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

/**
 * A contact to attach belongs to the member who met them until someone attaches them. A colleague neither plans nor
 * logs anything with them.
 */
final class ContactToAttachOfAnother extends NotAllowed
{
}
