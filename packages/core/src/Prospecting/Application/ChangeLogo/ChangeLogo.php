<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ChangeLogo;

use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ChangeLogo
{
    public function __construct(
        public MemberId $prospectorId,
        public OrganizationId $id,
        public \SplFileInfo $logo,
    ) {
    }
}
