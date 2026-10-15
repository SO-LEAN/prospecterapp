<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

final readonly class ChangeMyOrganizationLogoRequest
{
    public function __construct(public \SplFileInfo $logo)
    {
    }
}
