<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A planned exchange was cancelled before it happened.
 */
final readonly class ExchangeCancelled extends ExchangeEvent
{
}
