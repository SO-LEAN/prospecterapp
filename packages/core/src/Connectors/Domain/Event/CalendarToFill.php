<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Event;

/**
 * The member's calendar at the provider lacks their upcoming exchanges (ADR 0133). The member connected, or the
 * calendar was created again.
 */
abstract readonly class CalendarToFill extends ConnectionEvent
{
}
