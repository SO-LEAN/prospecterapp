<?php

namespace App\Doctrine\Type;

use Prospecter\Core\Domain\User\UserId;

final class UserIdType extends IdentityType
{
    protected function identityClass(): string
    {
        return UserId::class;
    }
}
