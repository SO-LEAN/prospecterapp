<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

final readonly class SearchProspectedOrganizationsResponse
{
    /**
     * @param list<FoundOrganization> $organizations
     */
    public function __construct(
        public int $currentPage,
        public int $total,
        public int $totalPages,
        public array $organizations,
    ) {
    }
}
