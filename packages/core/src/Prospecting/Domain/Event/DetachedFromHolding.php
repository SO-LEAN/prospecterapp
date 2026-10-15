<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;

/**
 * The organization was detached from its holding.
 */
final readonly class DetachedFromHolding extends OrganizationEvent
{
}
