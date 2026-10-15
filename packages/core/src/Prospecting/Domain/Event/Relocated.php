<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;

/**
 * The organization moved, or its address was erased.
 */
final readonly class Relocated extends OrganizationEvent
{
}
