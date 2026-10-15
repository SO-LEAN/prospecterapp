<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\ChangeLogo;

use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\User\UserId;

final readonly class ChangeLogo
{
    public function __construct(
        public UserId $prospectorId,
        public OrganizationId $id,
        public \SplFileInfo $logo,
    ) {
    }
}
