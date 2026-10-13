<?php

namespace App\Api\Resource;

final readonly class DashboardResource
{
    /**
     * @param array<string, int>                $stats   counters of the user's organization, by name
     * @param list<OrganizationSummaryResource> $markers organizations that can be placed on a map
     */
    public function __construct(
        public string $organizationName,
        public array $stats,
        public array $markers,
    ) {
    }
}
