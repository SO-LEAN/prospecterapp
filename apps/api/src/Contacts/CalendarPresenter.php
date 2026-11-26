<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\CalendarLinksResource;
use App\Contacts\Resource\CalendarResource;
use App\Contacts\Resource\InterlocutorResource;
use App\Contacts\Resource\OrganizationExchangeResource;
use App\Prospecting\TimelineResourceFactory;
use App\ToDos\Resource\ToDoResource;
use App\ToDos\ToDoResourceFactory;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultCalendar\Calendar;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultCalendar\ConsultCalendarOutput;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * Presents the signed-in member's calendar over a period. Each exchange names who it is with, as the query read them:
 * the person, or the organization itself. Each exchange also gets its links. Only a member with the right to manage
 * personal data reads the calendar's to-dos, because their text may hold some. The calendar says what is late among
 * what it shows. One presenter serves one call and keeps the resource (ADR 0085, 0217).
 */
final class CalendarPresenter implements ConsultCalendarOutput
{
    public private(set) CalendarResource $resource;

    public function __construct(
        private readonly TimelineResourceFactory $timelineResourceFactory,
        private readonly CalendarLinksResource $links,
        private readonly ToDoResourceFactory $toDoResourceFactory,
        private readonly bool $readsToDos,
    ) {
    }

    public function present(Calendar $calendar): void
    {
        $toDos = $this->readsToDos ? array_map(fn (ToDoInList $toDo): ToDoResource => $this->toDoResourceFactory->of($toDo, $calendar->interlocutors->persons), $calendar->toDos) : [];
        $shown = [...array_map(static fn (ExchangeInTimeline $exchange): string => $exchange->id, $calendar->exchanges), ...array_map(static fn (ToDoResource $toDo): string => $toDo->id, $toDos)];

        $this->resource = new CalendarResource(
            array_map(fn (ExchangeInTimeline $exchange): OrganizationExchangeResource => new OrganizationExchangeResource($this->timelineResourceFactory->createCalendarExchange($exchange, $calendar), InterlocutorResource::of($exchange, $calendar->interlocutors)), $calendar->exchanges),
            $toDos,
            array_values(array_intersect($calendar->late, $shown)),
            $this->links,
        );
    }
}
