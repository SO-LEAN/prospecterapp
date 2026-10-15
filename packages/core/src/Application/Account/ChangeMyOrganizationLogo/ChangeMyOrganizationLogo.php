<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ChangeMyOrganizationLogo;

use Prospecter\Core\Domain\User\UserId;

final readonly class ChangeMyOrganizationLogo
{
    public function __construct(public UserId $userId, public \SplFileInfo $logo)
    {
    }
}
