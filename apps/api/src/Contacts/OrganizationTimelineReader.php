<?php

namespace App\Contacts;

use App\Contacts\Resource\OrganizationTimelineResource;
use App\Prospecting\TimelineResourceFactory;
use App\Shared\Links;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\Prospecting\Application\ConsultExchangesToMakeConform\ConsultExchangesToMakeConform;
use Prospecter\Core\Prospecting\Application\ConsultOrganizationTimeline\ConsultOrganizationTimeline;
use Prospecter\Core\Prospecting\Application\ExchangeResponse;
use Prospecter\Core\Prospecting\Application\KeptTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\Prospecting\Application\TimelineResponse;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;

/**
 * The timeline of an organization, composed of two contexts (ADR 0076): Prospecting knows its exchanges and the
 * person of each, PersonalData names the persons of the slice in one batch; the slice kept, then presented with
 * them (ADR 0084). A controller calls it after a command, which returns nothing, to answer with the fresh resource.
 */
final readonly class OrganizationTimelineReader
{
    public function __construct(
        private RefusalTranslatingHandler $consultOrganizationTimeline,
        private RefusalTranslatingHandler $consultExchangesToMakeConform,
        private RefusalTranslatingHandler $consultPersons,
        private TimelineResourceFactory $timelines,
        private Links $links,
    ) {
    }

    public function read(string $reader, OrganizationResponse $organization, ?string $after = null): OrganizationTimelineResource
    {
        $timeline = new KeptTimeline();
        ($this->consultOrganizationTimeline)(new ConsultOrganizationTimeline($reader, $organization->id, $after), $timeline);
        $presenter = OrganizationTimelinePresenter::ofTheTimeline($organization, $this->personsOf($reader, $timeline->timeline), $this->timelines, $this->links);
        $presenter->present($timeline->timeline);

        return $presenter->resource;
    }

    /**
     * Only its exchanges to make conform, their contact forgotten (ADR 0079).
     */
    public function readToMakeConform(string $reader, OrganizationResponse $organization, ?string $after = null): OrganizationTimelineResource
    {
        $timeline = new KeptTimeline();
        ($this->consultExchangesToMakeConform)(new ConsultExchangesToMakeConform($reader, $organization->id, $after), $timeline);
        $presenter = OrganizationTimelinePresenter::ofTheExchangesToMakeConform($organization, $this->personsOf($reader, $timeline->timeline), $this->timelines, $this->links);
        $presenter->present($timeline->timeline);

        return $presenter->resource;
    }

    /**
     * @return array<string, PersonResponse> the persons the exchanges of the slice were with, by id
     */
    private function personsOf(string $reader, TimelineResponse $timeline): array
    {
        $persons = new KeptPersons();
        ($this->consultPersons)(new ConsultPersons($reader, self::personIdsOf($timeline)), $persons);

        return $persons->persons;
    }

    /**
     * @return list<string> the persons the exchanges of the slice were with, each once
     */
    private static function personIdsOf(TimelineResponse $timeline): array
    {
        return array_values(array_unique(array_filter(array_map(static fn (ExchangeResponse $exchange): ?string => $exchange->personId, $timeline->exchanges))));
    }
}
