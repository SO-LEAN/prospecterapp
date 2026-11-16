<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * The calendar of the member at the provider lacks their exchanges to come: they connected, or it was created again
 * (ADR 0133).
 */
abstract readonly class CalendarToFill extends ConnectionEvent
{
}
