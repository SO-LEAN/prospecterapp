<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

final readonly class SearchProspectedOrganizations
{
    public function __construct(
        public string $prospectorId,
        public string $query = '',
        public ?string $after = null,
        public int $max = 20,
    ) {
    }
}
