<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Application\Port\GeoLocation;
use Prospecter\Core\Application\Service\GeoLocalizer;
use Prospecter\Core\Application\UseCaseConsumer;
use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Organization\OrganizationRepository;

final class StartProspecting extends OrganizationUseCase
{
    private readonly GeoLocalizer $geoLocalizer;

    public function __construct(OrganizationRepository $organizations, GeoLocation $geoLocation)
    {
        parent::__construct($organizations);
        $this->geoLocalizer = new GeoLocalizer($geoLocation);
    }

    public function execute(StartProspectingRequest $request, OrganizationPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $myOrganizationId = $consumer->getOrganizationId();
        $organization = Organization::prospectedBy($myOrganizationId, $this->organizations->nextIdentity(), $request->legalIdentity, $request->contactDetails);
        $organization->relocateTo($request->address, $this->geoLocalizer->locate($request->address));
        $organization->writeObservations($request->observations);

        if (null !== $request->holdingId) {
            $organization->attachToHolding($this->prospectedOrganizations->holdingProspectedBy($myOrganizationId, $request->holdingId)->id());
        }

        return $this->saveAndPresent($organization, $presenter);
    }
}
