<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\CalendarLinksResource;
use App\Contacts\Resource\CalendarResource;
use App\Contacts\Resource\ContactToAttachResource;
use App\Contacts\Resource\DayExchangeResource;
use App\Contacts\Resource\ExchangesToProcessLinksResource;
use App\Contacts\Resource\ExchangesToProcessResource;
use App\Contacts\Resource\InterlocutorResource;
use App\Contacts\Resource\MyDayLinksResource;
use App\Contacts\Resource\MyDayResource;
use App\Contacts\Resource\OrganizationExchangeResource;
use App\Contacts\Resource\OrganizationOfContactResource;
use App\Contacts\Resource\PlannedExchangesResource;
use App\Contacts\Resource\ToDoToProcessResource;
use App\Prospecting\Resource\ExchangeResource;
use App\Prospecting\TimelineResourceFactory;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use App\ToDos\Resource\ToDoResource;
use App\ToDos\ToDoResourceFactory;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultCalendar\ConsultCalendar;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultMyDay\ConsultMyDay;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultOverlappingExchanges\ConsultOverlappingExchanges;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultWhatIsToProcess\ConsultWhatIsToProcess;
use Prospecter\Core\Prospecting\Application\Agenda\Interlocutors;
use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Composes the signed-in member's agenda, calendar, day and exchanges to process (ADR 0217). Each one is a query of
 * Prospecting, which gives the exchanges, the to-dos and their links, and the organizations they are with. Accounts and
 * PersonalData name the persons, joined by their ids.
 */
final readonly class AgendaComposition
{
    public function __construct(
        private QueryHandler $consultOverlappingExchanges,
        private QueryHandler $consultCalendar,
        private QueryHandler $consultMyDay,
        private QueryHandler $consultWhatIsToProcess,
        private TimelineResourceFactory $timelines,
        private ToDoResourceFactory $toDoResourceFactory,
        private PersonsOfAView $personsOfAView,
        private Links $links,
        private IntentLinks $intentLinks,
    ) {
    }

    public function readOverlapping(string $holder, \DateTimeImmutable $startsAt, ?int $durationInMinutes): PlannedExchangesResource
    {
        $presenter = new OverlappingExchangesPresenter($this->timelines);
        $this->consultOverlappingExchanges->__invoke(new ConsultOverlappingExchanges($holder, $startsAt, $durationInMinutes), $presenter);
        $overlapping = $presenter->overlapping;
        $persons = $this->personsOfAView->shownTo($holder, self::contactsOf($overlapping->exchanges));

        return new PlannedExchangesResource(self::withTheirInterlocutors($overlapping->exchanges, $presenter->exchanges, $overlapping->interlocutors, $persons));
    }

    public function readCalendar(string $holder, \DateTimeImmutable $from, \DateTimeImmutable $until, \DateTimeImmutable $at): CalendarResource
    {
        $presenter = new CalendarPresenter($this->timelines, new CalendarLinksResource($this->links->to('api_me_calendar', self::period($from, $until)), $this->searchContacts()), $this->toDoResourceFactory, $this->managesPersonalData());
        $this->consultCalendar->__invoke(new ConsultCalendar(prospectorId: $holder, from: $from, until: $until, at: $at), $presenter);
        $calendar = $presenter->calendar;
        $persons = $this->personsOfAView->shownTo($holder, [...self::contactsOf($calendar->exchanges), ...self::contactsOfToDos($presenter->toDos)], self::holdersOf($presenter->toDos));

        return new CalendarResource(
            self::withTheirInterlocutors($calendar->exchanges, $presenter->exchanges, $calendar->interlocutors, $persons),
            array_map(fn (ToDoInList $toDo): ToDoResource => $this->toDoResourceFactory->of($toDo, $presenter->toDoLinks[$toDo->id], $persons), $presenter->toDos),
            $presenter->late,
            $presenter->links,
        );
    }

    public function readMyDay(string $holder, \DateTimeImmutable $from, \DateTimeImmutable $until, \DateTimeImmutable $at): MyDayResource
    {
        $presenter = new MyDayPresenter($this->timelines, new MyDayLinksResource($this->links->to('api_me_day', self::period($from, $until)), $this->searchContacts()));
        $this->consultMyDay->__invoke(new ConsultMyDay(prospectorId: $holder, from: $from, until: $until, at: $at), $presenter);
        $myDay = $presenter->myDay;
        $persons = $this->personsOfAView->shownTo($holder, self::contactsOf([...$myDay->today, ...$myDay->next]));

        return new MyDayResource(
            $this->ofTheDay($myDay->today, $presenter->exchanges, $myDay->interlocutors, $persons),
            $this->ofTheDay($myDay->next, $presenter->exchanges, $myDay->interlocutors, $persons),
            $presenter->links,
        );
    }

    public function readExchangesToProcess(string $holder, \DateTimeImmutable $at): ExchangesToProcessResource
    {
        $presenter = new ExchangesToProcessPresenter($this->timelines, new ExchangesToProcessLinksResource($this->links->to('api_me_exchanges_to_process')), $this->toDoResourceFactory, $this->links, $this->managesPersonalData());
        $this->consultWhatIsToProcess->__invoke(new ConsultWhatIsToProcess($holder, $at), $presenter);
        $toProcess = $presenter->toProcess;
        $persons = $this->personsOfAView->shownTo(
            $holder,
            [...self::contactsOf($toProcess->exchanges), ...self::contactsOfToDos($presenter->toDos), ...array_map(static fn (ContactToAttachInList $contact): string => $contact->personId, $toProcess->contactsToAttach)],
            self::holdersOf($presenter->toDos),
        );

        return new ExchangesToProcessResource(
            $this->ofTheDay($toProcess->exchanges, $presenter->exchanges, $toProcess->interlocutors, $persons),
            array_map(fn (ToDoInList $toDo): ToDoToProcessResource => new ToDoToProcessResource($this->toDoResourceFactory->of($toDo, $presenter->toDoLinks[$toDo->id], $persons), $toProcess->daysOverdue[$toDo->id]), $presenter->toDos),
            array_map(static fn (ContactToAttachInList $contact): ContactToAttachResource => new ContactToAttachResource(
                $contact->personId,
                $persons->nameOfContact($contact->personId),
                $persons->contact($contact->personId)?->email,
                $contact->lastMeetingAt,
                $contact->erasableFrom,
                $presenter->contactToAttachLinks[$contact->personId],
            ), $toProcess->contactsToAttach),
            $presenter->links,
        );
    }

    /**
     * @param list<ExchangeInTimeline>        $exchanges
     * @param array<string, ExchangeResource> $resources by exchange id
     *
     * @return list<OrganizationExchangeResource>
     */
    private static function withTheirInterlocutors(array $exchanges, array $resources, Interlocutors $interlocutors, PersonsShown $persons): array
    {
        return array_map(static fn (ExchangeInTimeline $exchange): OrganizationExchangeResource => new OrganizationExchangeResource($resources[$exchange->id], InterlocutorResource::of($exchange, $interlocutors, $persons)), $exchanges);
    }

    /**
     * Each exchange of a day names who it is with: the person and their organization, or the organization itself.
     *
     * @param list<ExchangeInTimeline>        $exchanges
     * @param array<string, ExchangeResource> $resources by exchange id
     *
     * @return list<DayExchangeResource>
     */
    private function ofTheDay(array $exchanges, array $resources, Interlocutors $interlocutors, PersonsShown $persons): array
    {
        return array_map(fn (ExchangeInTimeline $exchange): DayExchangeResource => new DayExchangeResource(
            $resources[$exchange->id],
            InterlocutorResource::of($exchange, $interlocutors, $persons),
            OrganizationOfContactResource::keptFor($exchange, $interlocutors->organizationsOfContacts, $this->links),
        ), $exchanges);
    }

    /**
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return list<string> the persons the exchanges are with
     */
    private static function contactsOf(array $exchanges): array
    {
        return array_values(array_filter(array_map(static fn (ExchangeInTimeline $exchange): ?string => $exchange->personId, $exchanges)));
    }

    /**
     * @param list<ToDoInList> $toDos
     *
     * @return list<string>
     */
    private static function contactsOfToDos(array $toDos): array
    {
        return array_values(array_filter(array_map(static fn (ToDoInList $toDo): ?string => $toDo->personId, $toDos)));
    }

    /**
     * @param list<ToDoInList> $toDos
     *
     * @return list<string>
     */
    private static function holdersOf(array $toDos): array
    {
        return array_map(static fn (ToDoInList $toDo): string => $toDo->heldBy, $toDos);
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
