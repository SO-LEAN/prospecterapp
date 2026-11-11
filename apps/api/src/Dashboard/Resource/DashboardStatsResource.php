<?php

namespace App\Dashboard\Resource;

/**
 * The figures of my organization: the organizations it prospects, those placed on the map, its contacts, the
 * exchanges it keeps and those of the month.
 */
final readonly class DashboardStatsResource
{
    public function __construct(
        public int $activeOrganizations,
        public int $locatedOrganizations,
        public int $contacts,
        public int $exchanges,
        public int $exchangesThisMonth,
    ) {
    }
}
