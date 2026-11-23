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
use App\ToDos\Resource\ToDoResource;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\ConsultWhatIsToProcess\ConsultWhatIsToProcessOutput;
use Prospecter\Core\Prospecting\Application\ConsultWhatIsToProcess\WhatIsToProcess;
use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * Presents what the signed-in member has to process. Each exchange names who it is with: the person and their
 * organization, or the organization itself. Each exchange also gets the links that handle it. The to-dos to process
 * are the ones the member may read. The contacts the member holds to attach are named. With the right to manage
 * personal data, each of them also gets the organization proposed to attach them to (ADR 0146). One presenter serves
 * one call. It gets the persons, the organizations and the to-dos as the API shows them, and keeps the resource
 * (ADR 0085).
 */
final class ExchangesToProcessPresenter implements ConsultWhatIsToProcessOutput
{
    public private(set) ExchangesToProcessResource $resource;

    /**
     * @param array<string, PersonCard>                          $persons                 the exchanges' persons and the contacts to attach, by id
     * @param array<string, OrganizationSheet>                   $organizations           the organizations that exchanges are held with directly, with no contact, by id
     * @param array<string, list<OrganizationOfContactResource>> $organizationsOfContacts by contact id
     * @param array<string, ToDoResource>                        $toDos                   the to-dos the member may read, by id
     */
    public function __construct(
        private readonly array $persons,
        private readonly array $organizations,
        private readonly TimelineResourceFactory $timelineResourceFactory,
        private readonly ExchangesToProcessLinksResource $linksOfTheList,
        private readonly array $organizationsOfContacts,
        private readonly array $toDos,
        private readonly Links $links,
        private readonly bool $proposesOrganizations,
    ) {
    }

    public function present(WhatIsToProcess $toProcess): void
    {
        $this->resource = new ExchangesToProcessResource(array_map(
            fn (ExchangeInTimeline $exchange): DayExchangeResource => new DayExchangeResource($this->timelineResourceFactory->createExchangeToProcess($exchange, $toProcess), InterlocutorResource::of($exchange, $this->persons, $this->organizations), OrganizationOfContactResource::keptFor($exchange, $this->organizationsOfContacts)),
            InterlocutorResource::known($toProcess->exchanges, $this->organizations),
        ), $this->toDosOf($toProcess), $this->contactsToAttachOf($toProcess), $this->linksOfTheList);
    }

    /**
     * @return list<ContactToAttachResource>
     */
    private function contactsToAttachOf(WhatIsToProcess $toProcess): array
    {
        $named = array_values(array_filter($toProcess->contactsToAttach, fn (ContactToAttachInList $contact): bool => isset($this->persons[$contact->personId])));

        return array_map($this->contactToAttach(...), $named);
    }

    private function contactToAttach(ContactToAttachInList $contact): ContactToAttachResource
    {
        $person = $this->persons[$contact->personId];
        $id = ['id' => $contact->personId];
        $links = new ContactToAttachLinksResource($this->links->to('api_contacts_show', $id), $this->proposesOrganizations ? $this->links->to('api_contacts_proposed_organization', $id) : null);

        return new ContactToAttachResource($contact->personId, new PersonNameResource($person->firstName, $person->lastName), $person->email, $contact->lastMeetingAt, $contact->erasableFrom, $links);
    }

    /**
     * @return list<ToDoToProcessResource>
     */
    private function toDosOf(WhatIsToProcess $toProcess): array
    {
        $readable = array_values(array_filter($toProcess->toDos, fn (ToDoInList $toDo): bool => isset($this->toDos[$toDo->id])));

        return array_map(fn (ToDoInList $toDo): ToDoToProcessResource => new ToDoToProcessResource($this->toDos[$toDo->id], $toProcess->daysOverdue[$toDo->id]), $readable);
    }
}
