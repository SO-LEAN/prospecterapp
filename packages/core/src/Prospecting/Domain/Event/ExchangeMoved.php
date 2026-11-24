<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A planned exchange was moved to another moment. It got a new duration or kept its own.
 */
final readonly class ExchangeMoved extends ExchangeEvent
{
}
