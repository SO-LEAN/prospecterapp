<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * An exchange's contact was forgotten. The exchange stays, and it must be redacted.
 */
final readonly class ExchangeContactForgotten extends ExchangeEvent
{
}
