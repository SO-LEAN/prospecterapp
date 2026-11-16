<?php

namespace App\Contacts;

use App\Contacts\Resource\DayExchangeResource;
use App\Contacts\Resource\ExchangesToProcessLinksResource;
use App\Contacts\Resource\ExchangesToProcessResource;
use App\Contacts\Resource\InterlocutorResource;
use App\Contacts\Resource\OrganizationOfContactResource;
use App\Prospecting\TimelineResourceFactory;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\ConsultExchangesToProcess\ConsultExchangesToProcessOutput;
use Prospecter\Core\Prospecting\Application\ConsultExchangesToProcess\ExchangesToProcess;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * The exchanges of the signed-in member to process, each with who it is with: the person and their organization, or
 * the organization itself, and the links handling it. Built for one call with the persons and the organizations
 * they are with, it keeps the resource (ADR 0085).
 */
final class ExchangesToProcessPresenter implements ConsultExchangesToProcessOutput
{
    public private(set) ExchangesToProcessResource $resource;

    /**
     * @param array<string, PersonCard>                          $persons                 the persons of the exchanges, by id
     * @param array<string, OrganizationSheet>                   $organizations           the organizations of the exchanges with them alone, by id
     * @param array<string, list<OrganizationOfContactResource>> $organizationsOfContacts by contact id
     */
    public function __construct(
        private readonly array $persons,
        private readonly array $organizations,
        private readonly TimelineResourceFactory $timelineResourceFactory,
        private readonly ExchangesToProcessLinksResource $links,
        private readonly array $organizationsOfContacts,
    ) {
    }

    public function present(ExchangesToProcess $toProcess): void
    {
        $this->resource = new ExchangesToProcessResource(array_map(
            fn (ExchangeInTimeline $exchange): DayExchangeResource => new DayExchangeResource($this->timelineResourceFactory->createExchangeToProcess($exchange, $toProcess), InterlocutorResource::of($exchange, $this->persons, $this->organizations), OrganizationOfContactResource::keptFor($exchange, $this->organizationsOfContacts)),
            InterlocutorResource::known($toProcess->exchanges, $this->organizations),
        ), $this->links);
    }
}
