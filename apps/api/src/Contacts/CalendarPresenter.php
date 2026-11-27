<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\CalendarLinksResource;
use App\Prospecting\Resource\ExchangeResource;
use App\Prospecting\TimelineResourceFactory;
use App\ToDos\Resource\ToDoLinksResource;
use App\ToDos\ToDoResourceFactory;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultCalendar\Calendar;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultCalendar\ConsultCalendarOutput;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * Presents what Prospecting shows of the signed-in member's calendar over a period: each exchange and each to-do with
 * its links, and who they are with, by ids. Only a member with the right to manage personal data reads the calendar's
 * to-dos, because their text may hold some. The calendar says what is late among what it shows. One presenter serves
 * one call (ADR 0085, 0217).
 */
final class CalendarPresenter implements ConsultCalendarOutput
{
    public private(set) Calendar $calendar;

    /** @var array<string, ExchangeResource> by exchange id */
    public private(set) array $exchanges = [];

    /** @var list<ToDoInList> the to-dos the member reads */
    public private(set) array $toDos = [];

    /** @var array<string, ToDoLinksResource> by to-do id */
    public private(set) array $toDoLinks = [];

    /** @var list<string> the ids of the exchanges and to-dos shown that are late */
    public private(set) array $late = [];

    public function __construct(
        private readonly TimelineResourceFactory $timelineResourceFactory,
        public readonly CalendarLinksResource $links,
        private readonly ToDoResourceFactory $toDoResourceFactory,
        private readonly bool $readsToDos,
    ) {
    }

    public function present(Calendar $calendar): void
    {
        $this->calendar = $calendar;
        $this->toDos = $this->readsToDos ? $calendar->toDos : [];

        foreach ($calendar->exchanges as $exchange) {
            $this->exchanges[$exchange->id] = $this->timelineResourceFactory->createCalendarExchange($exchange, $calendar);
        }

        foreach ($this->toDos as $toDo) {
            $this->toDoLinks[$toDo->id] = $this->toDoResourceFactory->linksOf($toDo);
        }

        $this->late = array_values(array_intersect($calendar->late, [...array_keys($this->exchanges), ...array_keys($this->toDoLinks)]));
    }
}
