<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Relocate;

use Prospecter\Core\Prospecting\Domain\Address;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class Relocate
{
    public function __construct(
        public MemberId $prospectorId,
        public OrganizationId $id,
        public ?Address $address,
    ) {
    }
}
