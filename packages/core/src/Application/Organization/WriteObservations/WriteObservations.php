<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\WriteObservations;

use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\User\UserId;

final readonly class WriteObservations
{
    public function __construct(
        public UserId $prospectorId,
        public OrganizationId $id,
        public ?string $observations,
    ) {
    }
}
