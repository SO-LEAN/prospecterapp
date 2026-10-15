<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganization;

use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;

final readonly class ConsultOrganizationHandler
{
    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
    }

    public function __invoke(ConsultOrganization $query): OrganizationResponse
    {
        $prospector = $this->prospectors->get($query->prospectorId);
        $organization = $this->organizations->get($query->id);
        $organization->ensureReachableBy($prospector);

        return OrganizationResponse::of($organization, $prospector);
    }
}
