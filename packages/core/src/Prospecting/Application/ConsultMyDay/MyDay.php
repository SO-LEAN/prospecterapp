<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultMyDay;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;

final readonly class MyDay
{
    /**
     * @param list<ExchangeInTimeline> $today    those the prospector planned in the day, still planned, the soonest first
     * @param list<ExchangeInTimeline> $next     the first ones they planned after the day, the soonest first
     * @param list<string>             $toHandle the ids of those of the day whose time has come, to mark done or missed:
     *                                           `ExchangePossibilities` offers its holder both under the same
     *                                           condition, once its time has come
     */
    public function __construct(public array $today, public array $next, public array $toHandle)
    {
    }
}
