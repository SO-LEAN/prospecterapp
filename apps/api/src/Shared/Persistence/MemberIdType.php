<?php

namespace App\Shared\Persistence;

use Prospecter\Core\SharedKernel\Domain\MemberId;

final class MemberIdType extends IdentityType
{
    protected function identityClass(): string
    {
        return MemberId::class;
    }
}
