<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Policy;

use Prospecter\Core\Accounts\Domain\Model\Role;

/**
 * Who reads and manages the personal data that PersonalData keeps (ADR 0031): a member with this right, or an
 * administrator. The member and the member's read model both ask this policy (ADR 0088).
 */
final class PersonalDataRight
{
    #[\NoDiscard]
    public static function isGrantedBy(Role ...$roles): bool
    {
        return in_array(Role::PersonalData, $roles, true) || in_array(Role::Admin, $roles, true);
    }
}
