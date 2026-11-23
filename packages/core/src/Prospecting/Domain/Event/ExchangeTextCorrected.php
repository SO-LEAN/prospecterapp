<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A prospector corrected an exchange's text. Its other facts did not change.
 */
final readonly class ExchangeTextCorrected extends ExchangeEvent
{
}
