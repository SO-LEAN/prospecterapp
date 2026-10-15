<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

final readonly class SearchProspectedOrganizationsRequest
{
    public function __construct(
        public int $page = 1,
        public string $query = '',
        public int $maxByPage = 20,
    ) {
    }
}
