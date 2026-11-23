<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * The organization was located before Prospecter kept municipalities. Its address was geocoded again, and it got its
 * municipality (ADR 0142).
 */
final readonly class LocatedAgain extends OrganizationEvent
{
}
