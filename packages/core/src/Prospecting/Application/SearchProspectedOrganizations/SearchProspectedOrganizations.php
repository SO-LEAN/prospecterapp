<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

final readonly class SearchProspectedOrganizations
{
    public function __construct(
        public string $prospectorId,
        public int $page = 1,
        public string $query = '',
        public int $maxByPage = 20,
    ) {
    }
}
