<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A prospector marked a planned exchange done, with its report, once its time had come.
 */
final readonly class ExchangeMarkedDone extends ExchangeEvent
{
}
