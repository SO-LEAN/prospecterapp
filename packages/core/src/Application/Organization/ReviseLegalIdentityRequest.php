<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Domain\Organization\LegalIdentity;
use Prospecter\Core\Domain\Organization\OrganizationId;

final readonly class ReviseLegalIdentityRequest
{
    public function __construct(
        public OrganizationId $id,
        public LegalIdentity $legalIdentity,
    ) {
    }
}
