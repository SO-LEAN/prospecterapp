<?php

namespace App\Contacts;

use App\Contacts\Resource\OrganizationTimelineResource;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\Prospecting\Application\ConsultExchangesToMakeConform\ConsultExchangesToMakeConform;
use Prospecter\Core\Prospecting\Application\ConsultOrganizationTimeline\ConsultOrganizationTimeline;
use Prospecter\Core\Prospecting\Application\ExchangeResponse;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\Prospecting\Application\TimelineResponse;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;

/**
 * The timeline of an organization, composed of two contexts (ADR 0076): Prospecting knows its exchanges and the
 * person of each, PersonalData names the persons of the slice in one batch. A controller calls it after a
 * command, which returns nothing, to answer with the fresh resource.
 */
final readonly class OrganizationTimelineReader
{
    public function __construct(
        private RefusalTranslatingHandler $consultOrganizationTimeline,
        private RefusalTranslatingHandler $consultExchangesToMakeConform,
        private RefusalTranslatingHandler $consultPersons,
        private OrganizationTimelinePresenter $presenter,
    ) {
    }

    public function read(string $reader, OrganizationResponse $organization, ?string $after = null): OrganizationTimelineResource
    {
        $timeline = ($this->consultOrganizationTimeline)(new ConsultOrganizationTimeline($reader, $organization->id, $after));

        return $this->presenter->present(
            $organization,
            $timeline,
            ($this->consultPersons)(new ConsultPersons($reader, self::personIdsOf($timeline))),
        );
    }

    /**
     * Only its exchanges to make conform, their contact forgotten (ADR 0079).
     */
    public function readToMakeConform(string $reader, OrganizationResponse $organization, ?string $after = null): OrganizationTimelineResource
    {
        $timeline = ($this->consultExchangesToMakeConform)(new ConsultExchangesToMakeConform($reader, $organization->id, $after));

        return $this->presenter->presentToMakeConform(
            $organization,
            $timeline,
            ($this->consultPersons)(new ConsultPersons($reader, self::personIdsOf($timeline))),
        );
    }

    /**
     * @return list<string> the persons the exchanges of the slice were with, each once
     */
    private static function personIdsOf(TimelineResponse $timeline): array
    {
        return array_values(array_unique(array_filter(array_map(static fn (ExchangeResponse $exchange): ?string => $exchange->personId, $timeline->exchanges))));
    }
}
