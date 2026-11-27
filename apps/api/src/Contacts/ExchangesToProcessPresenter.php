<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ContactToAttachLinksResource;
use App\Contacts\Resource\ExchangesToProcessLinksResource;
use App\Prospecting\Resource\ExchangeResource;
use App\Prospecting\TimelineResourceFactory;
use App\Shared\Links;
use App\ToDos\Resource\ToDoLinksResource;
use App\ToDos\ToDoResourceFactory;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultWhatIsToProcess\ConsultWhatIsToProcessOutput;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultWhatIsToProcess\WhatIsToProcess;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * Presents what Prospecting shows of what the signed-in member has to process: each exchange with the links that
 * handle it, the to-dos and the contacts to attach, and who they are with, by ids. Only a member with the right to
 * manage personal data reads the to-dos to process, because their text may hold some. With that right, each contact
 * to attach also links to the organization proposed to attach them to (ADR 0146). One presenter serves one call
 * (ADR 0085, 0217).
 */
final class ExchangesToProcessPresenter implements ConsultWhatIsToProcessOutput
{
    public private(set) WhatIsToProcess $toProcess;

    /** @var array<string, ExchangeResource> by exchange id */
    public private(set) array $exchanges = [];

    /** @var list<ToDoInList> the to-dos the member reads */
    public private(set) array $toDos = [];

    /** @var array<string, ToDoLinksResource> by to-do id */
    public private(set) array $toDoLinks = [];

    /** @var array<string, ContactToAttachLinksResource> by contact id */
    public private(set) array $contactToAttachLinks = [];

    public function __construct(
        private readonly TimelineResourceFactory $timelineResourceFactory,
        public readonly ExchangesToProcessLinksResource $links,
        private readonly ToDoResourceFactory $toDoResourceFactory,
        private readonly Links $routes,
        private readonly bool $managesPersonalData,
    ) {
    }

    public function present(WhatIsToProcess $toProcess): void
    {
        $this->toProcess = $toProcess;
        $this->toDos = $this->managesPersonalData ? $toProcess->toDos : [];

        foreach ($toProcess->exchanges as $exchange) {
            $this->exchanges[$exchange->id] = $this->timelineResourceFactory->createExchangeToProcess($exchange, $toProcess);
        }

        foreach ($this->toDos as $toDo) {
            $this->toDoLinks[$toDo->id] = $this->toDoResourceFactory->linksOf($toDo);
        }

        foreach ($toProcess->contactsToAttach as $contact) {
            $id = ['id' => $contact->personId];
            $this->contactToAttachLinks[$contact->personId] = new ContactToAttachLinksResource($this->routes->to('api_contacts_show', $id), $this->managesPersonalData ? $this->routes->to('api_contacts_proposed_organization', $id) : null);
        }
    }
}
