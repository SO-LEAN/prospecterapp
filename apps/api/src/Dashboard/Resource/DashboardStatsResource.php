<?php

declare(strict_types=1);

namespace App\Dashboard\Resource;

/**
 * My organization's figures: the organizations it prospects, the organizations placed on the map, its contacts, the
 * exchanges it keeps, and the exchanges in the current month.
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
