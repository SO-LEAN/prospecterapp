<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Application\UseCase;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final class ChangeContactDetails extends UseCase
{
    private readonly OrganizationResponses $responses;

    public function __construct(private readonly OrganizationRepository $organizations, private readonly UserRepository $users)
    {
        $this->responses = new OrganizationResponses($organizations);
    }

    public function execute(ChangeContactDetailsRequest $request, OrganizationPresenter $presenter): mixed
    {
        $prospector = $this->users->get($request->prospectorId);
        $organization = $this->organizations->get($request->id);
        $organization->changeContactDetails($request->contactDetails, by: $prospector);
        $this->organizations->save($organization);

        return $presenter->present($this->responses->of($organization, $prospector));
    }
}
