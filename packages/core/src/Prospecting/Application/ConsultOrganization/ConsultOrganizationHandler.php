<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOrganization;

use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final readonly class ConsultOrganizationHandler implements QueryHandler
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    public function __invoke(ConsultOrganization $query): OrganizationResponse
    {
        return OrganizationResponse::of($this->organizations->get(new OrganizationId($query->id)));
    }
}
