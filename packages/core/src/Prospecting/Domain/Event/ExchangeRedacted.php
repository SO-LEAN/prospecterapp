<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A prospector declared redacted the text of an exchange whose contact was forgotten.
 */
final readonly class ExchangeRedacted extends ExchangeEvent
{
}
