<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultCalendar;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;

final readonly class Calendar
{
    /**
     * @param list<ExchangeInTimeline> $exchanges those the prospector holds in the period, at their moment, the
     *                                            soonest first, cancelled ones too
     */
    public function __construct(public array $exchanges)
    {
    }
}
