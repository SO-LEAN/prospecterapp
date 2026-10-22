<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A prospector corrected the text of an exchange, its other facts unchanged.
 */
final readonly class ExchangeTextCorrected extends ExchangeEvent
{
}
