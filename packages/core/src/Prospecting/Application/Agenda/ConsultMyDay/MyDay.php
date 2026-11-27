<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda\ConsultMyDay;

use Prospecter\Core\Prospecting\Application\Agenda\Interlocutors;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;

final readonly class MyDay
{
    /**
     * @param list<ExchangeInTimeline> $today    the exchanges the prospector planned in the day and still has planned, the
     *                                           soonest first
     * @param list<ExchangeInTimeline> $next     the first exchanges they planned after the day, the soonest first
     * @param list<string>             $toHandle the ids of the day's exchanges whose time has come, to mark done or
     *                                           missed. `ExchangePossibilities` offers both to the holder under the
     *                                           same condition: once the exchange's time has come
     */
    public function __construct(public array $today, public array $next, public array $toHandle, public Interlocutors $interlocutors)
    {
    }
}
