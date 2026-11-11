<?php

namespace App\Dashboard\Resource;

use App\Prospecting\Resource\OrganizationSummaryResource;

final readonly class DashboardResource
{
    /**
     * @param list<OrganizationSummaryResource> $markers organizations that can be placed on a map
     */
    public function __construct(
        public string $organizationName,
        public DashboardStatsResource $stats,
        public array $markers,
    ) {
    }
}
