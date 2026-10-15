<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganization;

use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultOrganizationHandler implements QueryHandler
{
    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors)
    {
    }

    public function __invoke(ConsultOrganization $query): OrganizationResponse
    {
        $prospector = $this->prospectors->get(new MemberId($query->prospectorId));
        $organization = $this->organizations->get(new OrganizationId($query->id));
        $organization->ensureReachableBy($prospector);

        return OrganizationResponse::of($organization, $prospector);
    }
}
