<?php

namespace App\Contacts;

use App\Contacts\Resource\InterlocutorResource;
use App\Contacts\Resource\OrganizationExchangeResource;
use App\Contacts\Resource\PlannedExchangesResource;
use App\Prospecting\TimelineResourceFactory;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges\ConsultOverlappingExchangesOutput;
use Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges\OverlappingExchanges;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * The exchanges of the signed-in member overlapping a slot, each with who it is with: the person, or the
 * organization itself. Built for one call with the persons and the organizations they are with, it keeps the
 * resource (ADR 0085).
 */
final class OverlappingExchangesPresenter implements ConsultOverlappingExchangesOutput
{
    public private(set) PlannedExchangesResource $resource;

    /**
     * @param array<string, PersonCard>        $persons       the persons of the exchanges, by id
     * @param array<string, OrganizationSheet> $organizations the organizations of the exchanges with them alone, by id
     */
    public function __construct(
        private readonly array $persons,
        private readonly array $organizations,
        private readonly TimelineResourceFactory $timelineResourceFactory,
    ) {
    }

    public function present(OverlappingExchanges $overlapping): void
    {
        $this->resource = new PlannedExchangesResource(array_map(
            fn (ExchangeInTimeline $exchange): OrganizationExchangeResource => new OrganizationExchangeResource($this->timelineResourceFactory->createExchange($exchange), InterlocutorResource::of($exchange, $this->persons, $this->organizations)),
            $overlapping->exchanges,
        ));
    }
}
