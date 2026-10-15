<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\RemoveOrganizationLogo;

final readonly class RemoveOrganizationLogoRequest
{
    public function __construct(public int $organizationId)
    {
    }
}
