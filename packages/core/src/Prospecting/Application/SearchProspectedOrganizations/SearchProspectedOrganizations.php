<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class SearchProspectedOrganizations
{
    public function __construct(
        public MemberId $prospectorId,
        public int $page = 1,
        public string $query = '',
        public int $maxByPage = 20,
    ) {
    }
}
