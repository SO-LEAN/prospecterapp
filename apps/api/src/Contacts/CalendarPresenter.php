<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\CalendarLinksResource;
use App\Contacts\Resource\CalendarResource;
use App\Contacts\Resource\InterlocutorResource;
use App\Contacts\Resource\OrganizationExchangeResource;
use App\Prospecting\TimelineResourceFactory;
use App\ToDos\Resource\ToDoResource;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\ConsultCalendar\Calendar;
use Prospecter\Core\Prospecting\Application\ConsultCalendar\ConsultCalendarOutput;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * The calendar of the signed-in member over a period, each exchange with who it is with: the person, or the
 * organization itself, and its links; the to-dos they may read, and what is late among what it shows. Built for one
 * call with the persons and the organizations they are with and the to-dos as the API shows them, it keeps the
 * resource (ADR 0085).
 */
final class CalendarPresenter implements ConsultCalendarOutput
{
    public private(set) CalendarResource $resource;

    /**
     * @param array<string, PersonCard>        $persons       the persons of the exchanges, by id
     * @param array<string, OrganizationSheet> $organizations the organizations of the exchanges with them alone, by id
     * @param array<string, ToDoResource>      $toDos         the to-dos the member may read, by id
     */
    public function __construct(
        private readonly array $persons,
        private readonly array $organizations,
        private readonly TimelineResourceFactory $timelineResourceFactory,
        private readonly CalendarLinksResource $links,
        private readonly array $toDos,
    ) {
    }

    public function present(Calendar $calendar): void
    {
        $exchanges = InterlocutorResource::known($calendar->exchanges, $this->organizations);
        $toDos = array_values(array_filter(array_map(fn (ToDoInList $toDo): ?ToDoResource => $this->toDos[$toDo->id] ?? null, $calendar->toDos)));
        $shown = [...array_map(static fn (ExchangeInTimeline $exchange): string => $exchange->id, $exchanges), ...array_map(static fn (ToDoResource $toDo): string => $toDo->id, $toDos)];

        $this->resource = new CalendarResource(
            array_map(fn (ExchangeInTimeline $exchange): OrganizationExchangeResource => new OrganizationExchangeResource($this->timelineResourceFactory->createCalendarExchange($exchange, $calendar), InterlocutorResource::of($exchange, $this->persons, $this->organizations)), $exchanges),
            $toDos,
            array_values(array_intersect($calendar->late, $shown)),
            $this->links,
        );
    }
}
