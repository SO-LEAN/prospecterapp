<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\InterlocutorResource;
use App\Contacts\Resource\OrganizationExchangeResource;
use App\Contacts\Resource\PlannedExchangesResource;
use App\Prospecting\TimelineResourceFactory;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultOverlappingExchanges\ConsultOverlappingExchangesOutput;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultOverlappingExchanges\OverlappingExchanges;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;

/**
 * Presents the signed-in member's exchanges that overlap a slot. Each exchange names who it is with, as the query read
 * them: the person, or the organization itself. One presenter serves one call and keeps the resource (ADR 0085, 0217).
 */
final class OverlappingExchangesPresenter implements ConsultOverlappingExchangesOutput
{
    public private(set) PlannedExchangesResource $resource;

    public function __construct(private readonly TimelineResourceFactory $timelineResourceFactory)
    {
    }

    public function present(OverlappingExchanges $overlapping): void
    {
        $this->resource = new PlannedExchangesResource(array_map(
            fn (ExchangeInTimeline $exchange): OrganizationExchangeResource => new OrganizationExchangeResource($this->timelineResourceFactory->createExchange($exchange), InterlocutorResource::of($exchange, $overlapping->interlocutors)),
            $overlapping->exchanges,
        ));
    }
}
