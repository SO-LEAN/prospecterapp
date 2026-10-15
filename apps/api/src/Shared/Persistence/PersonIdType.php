<?php

namespace App\Shared\Persistence;

use Prospecter\Core\SharedKernel\Domain\PersonId;

final class PersonIdType extends IdentityType
{
    protected function identityClass(): string
    {
        return PersonId::class;
    }
}
