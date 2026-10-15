<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Application\Port\GeoLocation;
use Prospecter\Core\Application\Service\GeoLocalizer;
use Prospecter\Core\Application\UseCase;
use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final class StartProspecting extends UseCase
{
    private readonly OrganizationResponses $responses;
    private readonly GeoLocalizer $geoLocalizer;

    public function __construct(private readonly OrganizationRepository $organizations, private readonly UserRepository $users, GeoLocation $geoLocation)
    {
        $this->responses = new OrganizationResponses($organizations);
        $this->geoLocalizer = new GeoLocalizer($geoLocation);
    }

    public function execute(StartProspectingRequest $request, OrganizationPresenter $presenter): mixed
    {
        $prospector = $this->users->get($request->prospectorId);
        $organization = Organization::prospectedBy($prospector, $this->organizations->nextIdentity(), $request->legalIdentity, $request->contactDetails);
        $organization->relocateTo($request->address, $this->geoLocalizer->locate($request->address), by: $prospector);
        $organization->writeObservations($request->observations, by: $prospector);

        if (null !== $request->holdingId) {
            $organization->attachToHolding($this->organizations->get($request->holdingId), by: $prospector);
        }

        $this->organizations->save($organization);

        return $presenter->present($this->responses->of($organization, $prospector));
    }
}
