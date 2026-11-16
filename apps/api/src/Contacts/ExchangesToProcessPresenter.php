<?php

namespace App\Contacts;

use App\Contacts\Resource\DayExchangeResource;
use App\Contacts\Resource\ExchangesToProcessLinksResource;
use App\Contacts\Resource\ExchangesToProcessResource;
use App\Contacts\Resource\InterlocutorResource;
use App\Contacts\Resource\OrganizationOfContactResource;
use App\Contacts\Resource\ToDoToProcessResource;
use App\Prospecting\TimelineResourceFactory;
use App\ToDos\Resource\ToDoResource;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\ConsultWhatIsToProcess\ConsultWhatIsToProcessOutput;
use Prospecter\Core\Prospecting\Application\ConsultWhatIsToProcess\WhatIsToProcess;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\ToDoInList;

/**
 * The exchanges of the signed-in member to process, each with who it is with: the person and their organization, or
 * the organization itself, and the links handling it; the to-dos to process they may read. Built for one call with the
 * persons and the organizations they are with and the to-dos as the API shows them, it keeps the resource (ADR 0085).
 */
final class ExchangesToProcessPresenter implements ConsultWhatIsToProcessOutput
{
    public private(set) ExchangesToProcessResource $resource;

    /**
     * @param array<string, PersonCard>                          $persons                 the persons of the exchanges, by id
     * @param array<string, OrganizationSheet>                   $organizations           the organizations of the exchanges with them alone, by id
     * @param array<string, list<OrganizationOfContactResource>> $organizationsOfContacts by contact id
     * @param array<string, ToDoResource>                        $toDos                   the to-dos the member may read, by id
     */
    public function __construct(
        private readonly array $persons,
        private readonly array $organizations,
        private readonly TimelineResourceFactory $timelineResourceFactory,
        private readonly ExchangesToProcessLinksResource $links,
        private readonly array $organizationsOfContacts,
        private readonly array $toDos,
    ) {
    }

    public function present(WhatIsToProcess $toProcess): void
    {
        $this->resource = new ExchangesToProcessResource(array_map(
            fn (ExchangeInTimeline $exchange): DayExchangeResource => new DayExchangeResource($this->timelineResourceFactory->createExchangeToProcess($exchange, $toProcess), InterlocutorResource::of($exchange, $this->persons, $this->organizations), OrganizationOfContactResource::keptFor($exchange, $this->organizationsOfContacts)),
            InterlocutorResource::known($toProcess->exchanges, $this->organizations),
        ), $this->toDosOf($toProcess), $this->links);
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
