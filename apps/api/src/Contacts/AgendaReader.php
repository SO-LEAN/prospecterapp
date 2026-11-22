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
use App\ToDos\Resource\ToDoResource;
use App\ToDos\ToDoReader;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\ConsultCalendar\ConsultCalendar;
use Prospecter\Core\Prospecting\Application\ConsultCalendar\KeptCalendar;
use Prospecter\Core\Prospecting\Application\ConsultMyDay\ConsultMyDay;
use Prospecter\Core\Prospecting\Application\ConsultMyDay\KeptMyDay;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\KeptOrganization;
use Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges\ConsultOverlappingExchanges;
use Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges\KeptOverlappingExchanges;
use Prospecter\Core\Prospecting\Application\ConsultWhatIsToProcess\ConsultWhatIsToProcess;
use Prospecter\Core\Prospecting\Application\ConsultWhatIsToProcess\KeptWhatIsToProcess;
use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * The agenda, the calendar, the day and the exchanges to process of the signed-in member, composed of two contexts: Prospecting knows the exchanges they
 * planned or noted, PersonalData names the persons they are with in one batch, Prospecting the organizations they
 * are with alone, and in the day those of the contacts, in one batch; the to-dos of the calendar and those to process
 * shown as the to-dos are, to a member with the right to manage personal data only, their text possibly holding some;
 * the answer kept, then presented with them (ADR 0085).
 */
final readonly class AgendaReader
{
    public function __construct(
        private QueryHandler $consultOverlappingExchanges,
        private QueryHandler $consultCalendar,
        private QueryHandler $consultMyDay,
        private QueryHandler $consultWhatIsToProcess,
        private QueryHandler $consultPersons,
        private QueryHandler $consultOrganization,
        private TimelineResourceFactory $timelines,
        private Links $links,
        private OrganizationsOfContacts $organizationsOfContacts,
        private ToDoReader $toDoReader,
        private IntentLinks $intentLinks,
    ) {
    }

    public function readOverlapping(string $holder, \DateTimeImmutable $startsAt, ?int $durationInMinutes): PlannedExchangesResource
    {
        $overlapping = new KeptOverlappingExchanges();
        $this->consultOverlappingExchanges->__invoke(new ConsultOverlappingExchanges($holder, $startsAt, $durationInMinutes), $overlapping);
        $exchanges = $overlapping->overlapping->exchanges;
        $presenter = new OverlappingExchangesPresenter($this->personsOf($holder, $exchanges), $this->organizationsPlannedWithAlone($exchanges), $this->timelines);
        $presenter->present($overlapping->overlapping);

        return $presenter->resource;
    }

    public function readCalendar(string $holder, \DateTimeImmutable $from, \DateTimeImmutable $until, \DateTimeImmutable $at): CalendarResource
    {
        $calendar = new KeptCalendar();
        $this->consultCalendar->__invoke(new ConsultCalendar(prospectorId: $holder, from: $from, until: $until, at: $at), $calendar);
        $exchanges = $calendar->calendar->exchanges;
        $presenter = new CalendarPresenter($this->personsOf($holder, $exchanges), $this->organizationsPlannedWithAlone($exchanges), $this->timelines, $this->calendarLinks($from, $until), $this->toDosReadBy($holder, $calendar->calendar->toDos));
        $presenter->present($calendar->calendar);

        return $presenter->resource;
    }

    public function readMyDay(string $holder, \DateTimeImmutable $from, \DateTimeImmutable $until, \DateTimeImmutable $at): MyDayResource
    {
        $myDay = new KeptMyDay();
        $this->consultMyDay->__invoke(new ConsultMyDay(prospectorId: $holder, from: $from, until: $until, at: $at), $myDay);
        $exchanges = [...$myDay->myDay->today, ...$myDay->myDay->next];
        $links = new MyDayLinksResource($this->links->to('api_me_day', self::period($from, $until)), $this->searchContacts());
        $presenter = new MyDayPresenter($this->personsOf($holder, $exchanges), $this->organizationsPlannedWithAlone($exchanges), $this->timelines, $links, $this->organizationsOfContacts->read($holder, self::contactsOf($exchanges)));
        $presenter->present($myDay->myDay);

        return $presenter->resource;
    }

    public function readExchangesToProcess(string $holder, \DateTimeImmutable $at): ExchangesToProcessResource
    {
        $toProcess = new KeptWhatIsToProcess();
        $this->consultWhatIsToProcess->__invoke(new ConsultWhatIsToProcess($holder, $at), $toProcess);
        $exchanges = $toProcess->toProcess->exchanges;
        $links = new ExchangesToProcessLinksResource($this->links->to('api_me_exchanges_to_process'));
        $persons = $this->personsAmong($holder, [...self::contactsOf($exchanges), ...array_map(static fn (ContactToAttachInList $contact): string => $contact->personId, $toProcess->toProcess->contactsToAttach)]);
        $presenter = new ExchangesToProcessPresenter($persons, $this->organizationsPlannedWithAlone($exchanges), $this->timelines, $links, $this->organizationsOfContacts->read($holder, self::contactsOf($exchanges)), $this->toDosReadBy($holder, $toProcess->toProcess->toDos), $this->links, $this->intentLinks->holds(User::PERSONAL_DATA));
        $presenter->present($toProcess->toProcess);

        return $presenter->resource;
    }

    /**
     * @param list<ToDoInList> $toDos
     *
     * @return array<string, ToDoResource> by id, none without the right to manage personal data
     */
    private function toDosReadBy(string $reader, array $toDos): array
    {
        if ([] === $toDos || !$this->intentLinks->holds(User::PERSONAL_DATA)) {
            return [];
        }

        return array_column($this->toDoReader->resourcesOf($reader, $toDos), null, 'id');
    }

    private function calendarLinks(\DateTimeImmutable $from, \DateTimeImmutable $until): CalendarLinksResource
    {
        return new CalendarLinksResource($this->links->to('api_me_calendar', self::period($from, $until)), $this->searchContacts());
    }

    /**
     * @return array{from: string, to: string}
     */
    private static function period(\DateTimeImmutable $from, \DateTimeImmutable $until): array
    {
        return ['from' => $from->format(\DATE_ATOM), 'to' => $until->format(\DATE_ATOM)];
    }

    /**
     * One plans with a contact searched by their name, as every prospector may (ADR 0129).
     */
    private function searchContacts(): LinkResource
    {
        return $this->links->to('api_contacts_search');
    }

    /**
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return array<string, PersonCard> by id
     */
    private function personsOf(string $reader, array $exchanges): array
    {
        return $this->personsAmong($reader, self::contactsOf($exchanges));
    }

    /**
     * @param list<string> $ids
     *
     * @return array<string, PersonCard> by id
     */
    private function personsAmong(string $reader, array $ids): array
    {
        $persons = new KeptPersons();
        $this->consultPersons->__invoke(new ConsultPersons($reader, array_values(array_unique($ids))), $persons);

        return $persons->persons;
    }

    /**
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return list<string> the ids of the persons they are with, once each
     */
    private static function contactsOf(array $exchanges): array
    {
        return array_values(array_unique(array_filter(array_map(static fn (ExchangeInTimeline $exchange): ?string => $exchange->personId, $exchanges))));
    }

    /**
     * One reading per organization: few in a slot or a period, most exchanges being with a contact. Stopping
     * prospecting erases the organization at once, its exchanges by a reaction after it: one no longer known is left
     * out, and its exchanges with it.
     *
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return array<string, OrganizationSheet> by id
     */
    private function organizationsPlannedWithAlone(array $exchanges): array
    {
        $ids = array_unique(array_map(static fn (ExchangeInTimeline $exchange): string => $exchange->organizationIds[0], array_filter($exchanges, static fn (ExchangeInTimeline $exchange): bool => null === $exchange->personId)));

        return array_filter(array_combine($ids, array_map($this->organization(...), $ids)));
    }

    private function organization(string $id): ?OrganizationSheet
    {
        $kept = new KeptOrganization();

        try {
            $this->consultOrganization->__invoke(new ConsultOrganization($id), $kept);
        } catch (UseCaseRefused $refusal) {
            return ErrorType::NotFound === $refusal->type ? null : throw $refusal;
        }

        return $kept->organization;
    }
}
