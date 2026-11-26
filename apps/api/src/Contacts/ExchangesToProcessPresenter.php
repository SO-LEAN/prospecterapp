<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ContactToAttachLinksResource;
use App\Contacts\Resource\ContactToAttachResource;
use App\Contacts\Resource\DayExchangeResource;
use App\Contacts\Resource\ExchangesToProcessLinksResource;
use App\Contacts\Resource\ExchangesToProcessResource;
use App\Contacts\Resource\InterlocutorResource;
use App\Contacts\Resource\OrganizationOfContactResource;
use App\Contacts\Resource\ToDoToProcessResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Prospecting\TimelineResourceFactory;
use App\Shared\Links;
use App\ToDos\ToDoResourceFactory;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultWhatIsToProcess\ConsultWhatIsToProcessOutput;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultWhatIsToProcess\WhatIsToProcess;
use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\PersonDescription;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * Presents what the signed-in member has to process. Each exchange names who it is with, as the query read them: the
 * person and their organization, or the organization itself. Each exchange also gets the links that handle it. Only a
 * member with the right to manage personal data reads the to-dos to process, because their text may hold some. The
 * contacts the member holds to attach are named. With that right, each of them also gets the organization proposed to
 * attach them to (ADR 0146). One presenter serves one call and keeps the resource (ADR 0085, 0217).
 */
final class ExchangesToProcessPresenter implements ConsultWhatIsToProcessOutput
{
    public private(set) ExchangesToProcessResource $resource;

    public function __construct(
        private readonly TimelineResourceFactory $timelineResourceFactory,
        private readonly ExchangesToProcessLinksResource $linksOfTheList,
        private readonly ToDoResourceFactory $toDoResourceFactory,
        private readonly Links $links,
        private readonly bool $managesPersonalData,
    ) {
    }

    public function present(WhatIsToProcess $toProcess): void
    {
        $this->resource = new ExchangesToProcessResource(array_map(
            fn (ExchangeInTimeline $exchange): DayExchangeResource => new DayExchangeResource(
                $this->timelineResourceFactory->createExchangeToProcess($exchange, $toProcess),
                InterlocutorResource::of($exchange, $toProcess->interlocutors),
                OrganizationOfContactResource::keptFor($exchange, $toProcess->interlocutors->organizationsOfContacts, $this->links),
            ),
            $toProcess->exchanges,
        ), $this->toDosOf($toProcess), $this->contactsToAttachOf($toProcess), $this->linksOfTheList);
    }

    /**
     * @return list<ContactToAttachResource>
     */
    private function contactsToAttachOf(WhatIsToProcess $toProcess): array
    {
        $persons = $toProcess->interlocutors->persons->contacts;
        $named = array_values(array_filter($toProcess->contactsToAttach, static fn (ContactToAttachInList $contact): bool => isset($persons[$contact->personId])));

        return array_map(fn (ContactToAttachInList $contact): ContactToAttachResource => $this->contactToAttach($contact, $persons[$contact->personId]), $named);
    }

    private function contactToAttach(ContactToAttachInList $contact, PersonDescription $person): ContactToAttachResource
    {
        $id = ['id' => $contact->personId];
        $links = new ContactToAttachLinksResource($this->links->to('api_contacts_show', $id), $this->managesPersonalData ? $this->links->to('api_contacts_proposed_organization', $id) : null);

        return new ContactToAttachResource($contact->personId, new PersonNameResource($person->firstName, $person->lastName), $person->email, $contact->lastMeetingAt, $contact->erasableFrom, $links);
    }

    /**
     * @return list<ToDoToProcessResource>
     */
    private function toDosOf(WhatIsToProcess $toProcess): array
    {
        if (!$this->managesPersonalData) {
            return [];
        }

        return array_map(fn (ToDoInList $toDo): ToDoToProcessResource => new ToDoToProcessResource($this->toDoResourceFactory->of($toDo, $toProcess->interlocutors->persons), $toProcess->daysOverdue[$toDo->id]), $toProcess->toDos);
    }
}
