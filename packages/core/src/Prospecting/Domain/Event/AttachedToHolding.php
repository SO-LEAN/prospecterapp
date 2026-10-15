<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;

/**
 * The organization was attached to a holding.
 */
final readonly class AttachedToHolding extends OrganizationEvent
{
}
