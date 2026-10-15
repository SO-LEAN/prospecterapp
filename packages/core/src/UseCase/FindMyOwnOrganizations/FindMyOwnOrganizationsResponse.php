<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\FindMyOwnOrganizations;

use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsResponse\Organization;

final readonly class FindMyOwnOrganizationsResponse
{
    /**
     * @param list<Organization> $organizations
     */
    public function __construct(
        public int $currentPage,
        public int $total,
        public int $totalPages,
        public array $organizations,
    ) {
    }
}
