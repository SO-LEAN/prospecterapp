<?php

namespace App\Doctrine\Type;

use Prospecter\Core\Domain\Organization\OrganizationId;

final class OrganizationIdType extends IdentityType
{
    protected function identityClass(): string
    {
        return OrganizationId::class;
    }
}
