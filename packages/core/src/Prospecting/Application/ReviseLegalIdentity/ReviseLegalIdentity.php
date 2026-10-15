<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ReviseLegalIdentity;

use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ReviseLegalIdentity
{
    public function __construct(
        public MemberId $prospectorId,
        public OrganizationId $id,
        public LegalIdentity $legalIdentity,
    ) {
    }
}
