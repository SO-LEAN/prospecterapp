<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\Event\OrganizationEvent;

/**
 * The organization, located before its municipality was kept, got it from its address geocoded again (ADR 0142).
 */
final readonly class LocatedAgain extends OrganizationEvent
{
}
