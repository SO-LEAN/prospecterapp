<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultCalendar;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;

final readonly class Calendar
{
    /**
     * @param list<ExchangeInTimeline> $exchanges those the prospector holds in the period, at their moment, the
     *                                            soonest first, cancelled ones too
     * @param list<string>             $movable   the ids of those the prospector may move now, by dragging them
     */
    public function __construct(public array $exchanges, public array $movable)
    {
    }
}
