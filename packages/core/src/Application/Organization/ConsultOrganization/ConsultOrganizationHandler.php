<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\ConsultOrganization;

use Prospecter\Core\Application\Organization\OrganizationResponse;
use Prospecter\Core\Application\Organization\OrganizationResponses;
use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\UserRepository;

final readonly class ConsultOrganizationHandler
{
    private OrganizationResponses $responses;

    public function __construct(private OrganizationRepository $organizations, private UserRepository $users)
    {
        $this->responses = new OrganizationResponses($organizations);
    }

    public function __invoke(ConsultOrganization $query): OrganizationResponse
    {
        $prospector = $this->users->get($query->prospectorId);
        $organization = $this->organizations->get($query->id);
        $organization->ensureReachableBy($prospector);

        return $this->responses->of($organization, $prospector);
    }
}
