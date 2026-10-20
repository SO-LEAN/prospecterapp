<?php

namespace App\Contacts;

use App\Contacts\Resource\OrganizationTimelineResource;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Prospecting\Application\ConsultOrganizationTimeline\ConsultOrganizationTimeline;
use Prospecter\Core\Prospecting\Application\ExchangeResponse;
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
        private RefusalTranslatingHandler $consultOrganization,
        private RefusalTranslatingHandler $consultPersons,
        private OrganizationTimelinePresenter $presenter,
    ) {
    }

    public function read(string $reader, string $organizationId, ?string $after = null): OrganizationTimelineResource
    {
        $timeline = ($this->consultOrganizationTimeline)(new ConsultOrganizationTimeline($reader, $organizationId, $after));
        $personIds = array_values(array_unique(array_filter(array_map(static fn (ExchangeResponse $exchange): ?string => $exchange->personId, $timeline->exchanges))));

        return $this->presenter->present(
            ($this->consultOrganization)(new ConsultOrganization($reader, $organizationId)),
            $timeline,
            ($this->consultPersons)(new ConsultPersons($reader, $personIds)),
        );
    }
}
