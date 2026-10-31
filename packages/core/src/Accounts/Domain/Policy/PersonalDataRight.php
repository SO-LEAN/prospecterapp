<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Policy;

use Prospecter\Core\Accounts\Domain\Model\Role;

/**
 * Who reads and manages the personal data PersonalData keeps (ADR 0031): a member with the right to, or an
 * administrator. Asked by the member and by its reading (ADR 0088).
 */
final class PersonalDataRight
{
    #[\NoDiscard]
    public static function isGrantedBy(Role ...$roles): bool
    {
        return in_array(Role::PersonalData, $roles, true) || in_array(Role::Admin, $roles, true);
    }
}
