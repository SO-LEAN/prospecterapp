<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\CalendarLinksResource;
use App\Contacts\Resource\CalendarResource;
use App\Contacts\Resource\ExchangesToProcessLinksResource;
use App\Contacts\Resource\ExchangesToProcessResource;
use App\Contacts\Resource\MyDayLinksResource;
use App\Contacts\Resource\MyDayResource;
use App\Contacts\Resource\PlannedExchangesResource;
use App\Prospecting\TimelineResourceFactory;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use App\ToDos\ToDoResourceFactory;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultCalendar\ConsultCalendar;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultMyDay\ConsultMyDay;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultOverlappingExchanges\ConsultOverlappingExchanges;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultWhatIsToProcess\ConsultWhatIsToProcess;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Reads the signed-in member's agenda, calendar, day and exchanges to process. Each read is one query of Prospecting,
 * which names who the exchanges and the to-dos are with, and a presenter built for the call (ADR 0085, 0217).
 */
final readonly class AgendaReader
{
    public function __construct(
        private QueryHandler $consultOverlappingExchanges,
        private QueryHandler $consultCalendar,
        private QueryHandler $consultMyDay,
        private QueryHandler $consultWhatIsToProcess,
        private TimelineResourceFactory $timelines,
        private ToDoResourceFactory $toDoResourceFactory,
        private Links $links,
        private IntentLinks $intentLinks,
    ) {
    }

    public function readOverlapping(string $holder, \DateTimeImmutable $startsAt, ?int $durationInMinutes): PlannedExchangesResource
    {
        $presenter = new OverlappingExchangesPresenter($this->timelines);
        $this->consultOverlappingExchanges->__invoke(new ConsultOverlappingExchanges($holder, $startsAt, $durationInMinutes), $presenter);

        return $presenter->resource;
    }

    public function readCalendar(string $holder, \DateTimeImmutable $from, \DateTimeImmutable $until, \DateTimeImmutable $at): CalendarResource
    {
        $presenter = new CalendarPresenter($this->timelines, new CalendarLinksResource($this->links->to('api_me_calendar', self::period($from, $until)), $this->searchContacts()), $this->toDoResourceFactory, $this->managesPersonalData());
        $this->consultCalendar->__invoke(new ConsultCalendar(prospectorId: $holder, from: $from, until: $until, at: $at), $presenter);

        return $presenter->resource;
    }

    public function readMyDay(string $holder, \DateTimeImmutable $from, \DateTimeImmutable $until, \DateTimeImmutable $at): MyDayResource
    {
        $presenter = new MyDayPresenter($this->timelines, new MyDayLinksResource($this->links->to('api_me_day', self::period($from, $until)), $this->searchContacts()), $this->links);
        $this->consultMyDay->__invoke(new ConsultMyDay(prospectorId: $holder, from: $from, until: $until, at: $at), $presenter);

        return $presenter->resource;
    }

    public function readExchangesToProcess(string $holder, \DateTimeImmutable $at): ExchangesToProcessResource
    {
        $presenter = new ExchangesToProcessPresenter($this->timelines, new ExchangesToProcessLinksResource($this->links->to('api_me_exchanges_to_process')), $this->toDoResourceFactory, $this->links, $this->managesPersonalData());
        $this->consultWhatIsToProcess->__invoke(new ConsultWhatIsToProcess($holder, $at), $presenter);

        return $presenter->resource;
    }

    /**
     * The to-dos' text may hold personal data, so only a member with the right to manage it reads them.
     */
    private function managesPersonalData(): bool
    {
        return $this->intentLinks->holds(User::PERSONAL_DATA);
    }

    /**
     * @return array{from: string, to: string}
     */
    private static function period(\DateTimeImmutable $from, \DateTimeImmutable $until): array
    {
        return ['from' => $from->format(\DATE_ATOM), 'to' => $until->format(\DATE_ATOM)];
    }

    /**
     * A member plans an exchange with a contact they search by name. Every prospector may do so (ADR 0129).
     */
    private function searchContacts(): LinkResource
    {
        return $this->links->to('api_contacts_search');
    }
}
