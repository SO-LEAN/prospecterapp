<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A prospector corrected where a meeting is held, or a video call's link. The exchange's other facts did not change.
 */
final readonly class ExchangeVenueCorrected extends ExchangeEvent
{
}
