<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A prospector declared an exchange's text redacted after its contact was forgotten.
 */
final readonly class ExchangeRedacted extends ExchangeEvent
{
}
