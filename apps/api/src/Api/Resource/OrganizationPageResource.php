<?php

namespace App\Api\Resource;

final readonly class OrganizationPageResource
{
    /**
     * @param list<OrganizationSummaryResource> $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $totalPages,
        public int $totalItems,
    ) {
    }
}
