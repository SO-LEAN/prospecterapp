<?php

declare(strict_types=1);

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
 * Presents the signed-in member's exchanges that overlap a slot. Each exchange names who it is with: the person, or
 * the organization itself. One presenter serves one call. It gets the persons and the organizations the exchanges
 * are with, and keeps the resource (ADR 0085).
 */
final class OverlappingExchangesPresenter implements ConsultOverlappingExchangesOutput
{
    public private(set) PlannedExchangesResource $resource;

    /**
     * @param array<string, PersonCard>        $persons       the exchanges' persons, by id
     * @param array<string, OrganizationSheet> $organizations the organizations that exchanges are held with directly, with no contact, by id
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
            InterlocutorResource::known($overlapping->exchanges, $this->organizations),
        ));
    }
}
