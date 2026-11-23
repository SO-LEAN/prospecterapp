<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A prospector marked a planned exchange missed. The contact did not come or answer.
 */
final readonly class ExchangeMarkedMissed extends ExchangeEvent
{
}
