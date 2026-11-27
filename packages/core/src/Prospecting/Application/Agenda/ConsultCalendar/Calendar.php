<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda\ConsultCalendar;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\ToDoInList;

final readonly class Calendar
{
    /**
     * @param list<ExchangeInTimeline> $exchanges the exchanges the prospector holds in the period, cancelled ones
     *                                            too, at their moment, the soonest first
     * @param list<string>             $movable   the ids of the exchanges the prospector may move now by dragging them
     * @param list<ToDoInList>         $toDos     the to-dos the prospector holds that are due on a day of the period,
     *                                            done or not, the soonest due first; a to-do without an hour comes
     *                                            before the same day's to-dos with an hour
     * @param list<string>             $late      the ids of the exchanges and to-dos that are late at the reading
     *                                            moment: a planned exchange whose slot has ended, or a to-do not done
     *                                            whose hour or day has passed
     */
    public function __construct(public array $exchanges, public array $movable, public array $toDos, public array $late)
    {
    }
}
