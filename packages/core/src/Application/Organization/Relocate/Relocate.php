<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\Relocate;

use Prospecter\Core\Domain\Organization\Address;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\User\UserId;

final readonly class Relocate
{
    public function __construct(
        public UserId $prospectorId,
        public OrganizationId $id,
        public ?Address $address,
    ) {
    }
}
