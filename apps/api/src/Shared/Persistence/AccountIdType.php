<?php

namespace App\Shared\Persistence;

use Prospecter\Core\SharedKernel\Domain\AccountId;

final class AccountIdType extends IdentityType
{
    protected function identityClass(): string
    {
        return AccountId::class;
    }
}
