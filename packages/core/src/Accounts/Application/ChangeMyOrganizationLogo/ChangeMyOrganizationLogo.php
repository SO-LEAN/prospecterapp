<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ChangeMyOrganizationLogo;

use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ChangeMyOrganizationLogo
{
    public function __construct(public MemberId $userId, public \SplFileInfo $logo)
    {
    }
}
