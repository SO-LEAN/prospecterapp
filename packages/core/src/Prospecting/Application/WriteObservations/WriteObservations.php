<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\WriteObservations;

use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class WriteObservations
{
    public function __construct(
        public MemberId $prospectorId,
        public OrganizationId $id,
        public ?string $observations,
    ) {
    }
}
