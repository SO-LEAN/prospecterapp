<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultCalendar;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\ToDoInList;

final readonly class Calendar
{
    /**
     * @param list<ExchangeInTimeline> $exchanges those the prospector holds in the period, at their moment, the
     *                                            soonest first, cancelled ones too
     * @param list<string>             $movable   the ids of those the prospector may move now, by dragging them
     * @param list<ToDoInList>         $toDos     those the prospector holds due on a day of the period, done or not,
     *                                            the soonest due first, a day without an hour before its hours
     * @param list<string>             $late      the ids of the exchanges and of the to-dos late at the moment of the
     *                                            reading: a planned exchange whose slot has ended, a to-do not done
     *                                            whose hour, or day, has passed
     */
    public function __construct(public array $exchanges, public array $movable, public array $toDos, public array $late)
    {
    }
}
