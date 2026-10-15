<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Domain\Organization\OrganizationId;

final readonly class RemoveLogoRequest
{
    public function __construct(
        public OrganizationId $id,
    ) {
    }
}
