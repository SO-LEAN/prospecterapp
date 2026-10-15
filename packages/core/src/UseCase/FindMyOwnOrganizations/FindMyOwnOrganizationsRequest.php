<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\FindMyOwnOrganizations;

final readonly class FindMyOwnOrganizationsRequest
{
    public function __construct(
        public int $page,
        public string $query,
        public int $maxByPage = 20,
    ) {
    }
}
