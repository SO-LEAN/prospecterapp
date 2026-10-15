<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Application\Port\GeoLocation;
use Prospecter\Core\Application\Service\GeoLocalizer;
use Prospecter\Core\Application\UseCaseConsumer;
use Prospecter\Core\Domain\Organization\OrganizationRepository;

final class Relocate extends OrganizationUseCase
{
    private readonly GeoLocalizer $geoLocalizer;

    public function __construct(OrganizationRepository $organizations, GeoLocation $geoLocation)
    {
        parent::__construct($organizations);
        $this->geoLocalizer = new GeoLocalizer($geoLocation);
    }

    public function execute(RelocateRequest $request, OrganizationPresenter $presenter, UseCaseConsumer $consumer): mixed
    {
        $organization = $this->prospectedBy($consumer, $request->id);
        $organization->relocateTo($request->address, $this->geoLocalizer->locate($request->address));

        return $this->saveAndPresent($organization, $presenter);
    }
}
