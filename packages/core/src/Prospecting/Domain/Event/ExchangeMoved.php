<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A planned exchange was moved to another moment, for a new duration or its own.
 */
final readonly class ExchangeMoved extends ExchangeEvent
{
}
