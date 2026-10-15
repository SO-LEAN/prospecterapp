<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\Relocate;

use Prospecter\Core\Application\Organization\OrganizationPresenter;
use Prospecter\Core\Application\Organization\OrganizationResponses;
use Prospecter\Core\Application\Port\GeoLocation;
use Prospecter\Core\Application\Service\GeoLocalizer;
use Prospecter\Core\Application\UseCase;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final class Relocate extends UseCase
{
    private readonly OrganizationResponses $responses;
    private readonly GeoLocalizer $geoLocalizer;

    public function __construct(private readonly OrganizationRepository $organizations, private readonly UserRepository $users, GeoLocation $geoLocation)
    {
        $this->responses = new OrganizationResponses($organizations);
        $this->geoLocalizer = new GeoLocalizer($geoLocation);
    }

    public function execute(RelocateRequest $request, OrganizationPresenter $presenter): mixed
    {
        $prospector = $this->users->get($request->prospectorId);
        $organization = $this->organizations->get($request->id);
        $organization->relocateTo($request->address, $this->geoLocalizer->locate($request->address), by: $prospector);
        $this->organizations->save($organization);

        return $presenter->present($this->responses->of($organization, $prospector));
    }
}
