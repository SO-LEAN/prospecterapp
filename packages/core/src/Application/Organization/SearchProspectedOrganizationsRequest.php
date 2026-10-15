<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Domain\User\UserId;

final readonly class SearchProspectedOrganizationsRequest
{
    public function __construct(
        public UserId $prospectorId,
        public int $page = 1,
        public string $query = '',
        public int $maxByPage = 20,
    ) {
    }
}
