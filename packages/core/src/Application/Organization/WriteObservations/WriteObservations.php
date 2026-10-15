<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\WriteObservations;

use Prospecter\Core\Application\Organization\OrganizationPresenter;
use Prospecter\Core\Application\Organization\OrganizationResponses;
use Prospecter\Core\Application\UseCase;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final class WriteObservations extends UseCase
{
    private readonly OrganizationResponses $responses;

    public function __construct(private readonly OrganizationRepository $organizations, private readonly UserRepository $users)
    {
        $this->responses = new OrganizationResponses($organizations);
    }

    public function execute(WriteObservationsRequest $request, OrganizationPresenter $presenter): mixed
    {
        $prospector = $this->users->get($request->prospectorId);
        $organization = $this->organizations->get($request->id);
        $organization->writeObservations($request->observations, by: $prospector);
        $this->organizations->save($organization);

        return $presenter->present($this->responses->of($organization, $prospector));
    }
}
