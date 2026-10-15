<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\IdentityType;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final class OrganizationIdType extends IdentityType
{
    protected function identityClass(): string
    {
        return OrganizationId::class;
    }
}
