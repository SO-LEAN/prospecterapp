<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Policy;

use Prospecter\Core\Accounts\Domain\Model\Role;

/**
 * Who administers an account. An administrator registers the account's members and configures its clients at the
 * providers (ADR 0125).
 */
final class AdministrationRight
{
    #[\NoDiscard]
    public static function isGrantedBy(Role ...$roles): bool
    {
        return in_array(Role::Admin, $roles, true);
    }
}
